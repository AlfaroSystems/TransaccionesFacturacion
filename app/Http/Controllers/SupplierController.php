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

        $suppliers = Supplier::with('contacts')
            ->when($request->search, function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('country', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
                    ->orWhereHas('contacts', function ($q) use ($request) {
                        $q->where('email', 'like', '%' . $request->search . '%');
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
            'email' => 'required|email|unique:suppliers,email',
            'phone' => 'nullable|string|max:20',
            'country' => 'required|string|max:100',
            'address' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'is_active' => 'boolean',

            'contacts' => 'required|array|min:1',
            'contacts.*.full_name' => 'required|string|max:255',
            'contacts.*.phone' => 'required|string|max:20',
            'contacts.*.email' => 'nullable|email',
            'contacts.*.is_active' => 'boolean',
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
                'is_active' => $request->boolean('is_active'),
            ]);

            foreach ($validated['contacts'] as $contact) {
                SupplierContact::create([
                    'id_supplier' => $supplier->id_supplier,
                    'full_name' => $contact['full_name'],
                    'phone' => $contact['phone'],
                    'email' => $contact['email'] ?? null,
                    'is_active' => isset($contact['is_active']),
                ]);
            }
        });

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Proveedor creado correctamente.');
    }

    /**
     * Ficha de detalle
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
     * Actualizar proveedor
     */
    public function update(Request $request, Supplier $supplier)
    {
        Gate::authorize('suppliers.editar');

        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:suppliers,code,' .
                $supplier->id_supplier . ',id_supplier',

            'name' => 'required|string|max:255',

            'email' => 'required|email|unique:suppliers,email,' .
                $supplier->id_supplier . ',id_supplier',

            'phone' => 'nullable|string|max:20',
            'country' => 'required|string|max:100',
            'address' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'is_active' => 'boolean',

            'contacts' => 'required|array|min:1',
            'contacts.*.full_name' => 'required|string|max:255',
            'contacts.*.phone' => 'required|string|max:20',
            'contacts.*.email' => 'nullable|email',
            'contacts.*.is_active' => 'boolean',
        ]);

        DB::transaction(function () use ($validated, $supplier, $request) {

            $supplier->update([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'country' => $validated['country'],
                'address' => $validated['address'] ?? null,
                'website' => $validated['website'] ?? null,
                'is_active' => $request->boolean('is_active'),
            ]);

            // Eliminar contactos anteriores
            $supplier->contacts()->delete();

            // Crear nuevamente los contactos
            foreach ($validated['contacts'] as $contact) {
                SupplierContact::create([
                    'id_supplier' => $supplier->id_supplier,
                    'full_name' => $contact['full_name'],
                    'phone' => $contact['phone'],
                    'email' => $contact['email'] ?? null,
                    'is_active' => isset($contact['is_active']),
                ]);
            }
        });

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Proveedor actualizado correctamente.');
    }

    /**
     * Eliminar proveedor
     */
    public function destroy(Supplier $supplier)
    {
        Gate::authorize('suppliers.eliminar');

        // Los contactos se eliminan automáticamente por ON DELETE CASCADE
        $supplier->delete();

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Proveedor eliminado correctamente.');
    }
}