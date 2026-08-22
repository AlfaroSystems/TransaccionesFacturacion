<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SupplierController extends Controller
{
    /**
     * Listado de proveedores
     */
    public function index(Request $request)
    {
        Gate::authorize('suppliers.ver');

        $search = $request->input('search');

        $suppliers = Supplier::with('contacts')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('code', 'like', '%' . $search . '%')
                        ->orWhere('country', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhereHas('contacts', function ($contactQuery) use ($search) {
                            $contactQuery
                                ->where('full_name', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%')
                                ->orWhere('phone', 'like', '%' . $search . '%');
                        });
                });
            })
            ->paginate(10)
            ->withQueryString();

        return view('suppliers.index', compact('suppliers'));
    }

    /**
     * Formulario de creación
     */
    public function create()
    {
        Gate::authorize('suppliers.crear');

        return view('suppliers.create');
    }

    /**
     * Guardar proveedor
     */
    public function store(Request $request)
    {
        Gate::authorize('suppliers.crear');

        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:suppliers,code',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:suppliers,email',
            'phone' => 'nullable|string|max:20',
            'country' => 'required|string|max:100',
            'address' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'is_active' => 'nullable|boolean',

            'contacts' => 'required|array|min:1',
            'contacts.*.full_name' => 'required|string|max:255',
            'contacts.*.phone' => 'required|string|max:20',
            'contacts.*.email' => 'nullable|email|max:255',
            'contacts.*.is_active' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $supplier = Supplier::create([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'country' => $validated['country'],
                'address' => $validated['address'] ?? null,
                'website' => $validated['website'] ?? null,
                'is_active' => $request->boolean('is_active', true),
            ]);

            foreach ($validated['contacts'] as $contactData) {
                SupplierContact::create([
                    'id_supplier' => $supplier->id_supplier,
                    'full_name' => $contactData['full_name'],
                    'phone' => $contactData['phone'],
                    'email' => $contactData['email'] ?? null,
                    'is_active' => isset($contactData['is_active']) ? (bool)$contactData['is_active'] : true,
                ]);
            }
        });

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Proveedor creado correctamente.');
    }

    /**
     * Mostrar proveedor
     */
    public function show(Supplier $supplier)
    {
        Gate::authorize('suppliers.ver');

        $supplier->load('contacts');

        return view('suppliers.show', compact('supplier'));
    }

    /**
     * Formulario de edición
     */
    public function edit(Supplier $supplier)
    {
        Gate::authorize('suppliers.editar');

        $supplier->load('contacts');

        return view('suppliers.edit', compact('supplier'));
    }

    /**
     * Actualizar proveedor y contactos (Smart Sync)
     */
    public function update(Request $request, Supplier $supplier)
    {
        Gate::authorize('suppliers.editar');

        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:suppliers,code,' . $supplier->id_supplier . ',id_supplier',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:suppliers,email,' . $supplier->id_supplier . ',id_supplier',
            'phone' => 'nullable|string|max:20',
            'country' => 'required|string|max:100',
            'address' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'is_active' => 'nullable|boolean',

            'contacts' => 'required|array|min:1',
            'contacts.*.id_contact' => 'nullable|integer|exists:supplier_contacts,id_contact',
            'contacts.*.full_name' => 'required|string|max:255',
            'contacts.*.phone' => 'required|string|max:20',
            'contacts.*.email' => 'nullable|email|max:255',
            'contacts.*.is_active' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($validated, $supplier, $request) {
            // 1. Actualizar datos del proveedor
            $supplier->update([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'country' => $validated['country'],
                'address' => $validated['address'] ?? null,
                'website' => $validated['website'] ?? null,
                'is_active' => $request->boolean('is_active', true),
            ]);

            // 2. Procesar contactos (Actualizar o Crear)
            $contactIds = [];

            foreach ($validated['contacts'] as $contactData) {
                if (!empty($contactData['id_contact'])) {
                    // Contacto existente
                    $contact = $supplier->contacts()
                        ->where('id_contact', $contactData['id_contact'])
                        ->first();

                    if (!$contact) {
                        abort(404, 'El contacto seleccionado no pertenece a este proveedor.');
                    }

                    $contact->update([
                        'full_name' => $contactData['full_name'],
                        'phone' => $contactData['phone'],
                        'email' => $contactData['email'] ?? null,
                        'is_active' => isset($contactData['is_active']) ? (bool)$contactData['is_active'] : true,
                    ]);

                    $contactIds[] = $contact->id_contact;
                } else {
                    // Contacto nuevo
                    $newContact = SupplierContact::create([
                        'id_supplier' => $supplier->id_supplier,
                        'full_name' => $contactData['full_name'],
                        'phone' => $contactData['phone'],
                        'email' => $contactData['email'] ?? null,
                        'is_active' => isset($contactData['is_active']) ? (bool)$contactData['is_active'] : true,
                    ]);

                    $contactIds[] = $newContact->id_contact;
                }
            }

            // 3. Eliminar contactos quitados del formulario
            $supplier->contacts()
                ->whereNotIn('id_contact', $contactIds)
                ->delete();
        });

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Proveedor y contactos actualizados correctamente.');
    }

    /**
     * Eliminar proveedor
     */
    public function destroy(Supplier $supplier)
    {
        Gate::authorize('suppliers.eliminar');

        DB::transaction(function () use ($supplier) {
            $supplier->contacts()->delete();
            $supplier->delete();
        });

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Proveedor eliminado correctamente.');
    }
}