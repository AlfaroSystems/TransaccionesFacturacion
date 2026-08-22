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
     * LISTAR PROVEEDORES
     */
    public function index(Request $request)
    {
        Gate::authorize('suppliers.ver');

        $search = $request->input('search');

        $suppliers = Supplier::with('contacts')
            ->when($search, function ($query) use ($search) {

                $query->where(function ($q) use ($search) {

                    $q->where('name', 'like', '%' . $search . '%')
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
     * FORMULARIO CREAR
     */
    public function create()
    {
        Gate::authorize('suppliers.crear');

        return view('suppliers.create');
    }


    /**
     * GUARDAR PROVEEDOR
     */
    public function store(Request $request)
    {
        Gate::authorize('suppliers.crear');

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:suppliers,email',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:20',
            ],

            'country' => [
                'required',
                'string',
                'max:100',
            ],

            'website' => [
                'nullable',
                'url',
                'max:255',
            ],

            'contacts' => [
                'required',
                'array',
                'min:1',
            ],

            'contacts.*.full_name' => [
                'required',
                'string',
                'max:255',
            ],

            'contacts.*.phone' => [
                'required',
                'string',
                'max:20',
            ],

            'contacts.*.email' => [
                'nullable',
                'email',
                'max:255',
            ],
        ]);


        DB::transaction(function () use ($validated) {

            /*
             * CREAR PROVEEDOR
             */

            $supplier = Supplier::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'country' => $validated['country'],
                'website' => $validated['website'] ?? null,
            ]);


            /*
             * CREAR CONTACTOS
             */

            foreach ($validated['contacts'] as $contactData) {

                $contact = new SupplierContact();

                $contact->id_supplier = $supplier->id_supplier;

                $contact->full_name = $contactData['full_name'];

                $contact->phone = $contactData['phone'];

                $contact->email = $contactData['email'] ?? null;

                /*
                 * El diagrama contempla is_active.
                 */
                $contact->is_active = true;

                $contact->save();
            }
        });


        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Proveedor creado correctamente.');
    }


    /**
     * MOSTRAR PROVEEDOR
     */
    public function show(Supplier $supplier)
    {
        Gate::authorize('suppliers.ver');

        $supplier->load('contacts');

        return view(
            'suppliers.show',
            compact('supplier')
        );
    }


    /**
     * FORMULARIO EDITAR
     */
    public function edit(Supplier $supplier)
    {
        Gate::authorize('suppliers.editar');

        $supplier->load('contacts');

        return view(
            'suppliers.edit',
            compact('supplier')
        );
    }


    /**
     * ACTUALIZAR PROVEEDOR + CONTACTOS
     */
    public function update(Request $request, Supplier $supplier)
    {
        Gate::authorize('suppliers.editar');


        /*
         * VALIDAR DATOS DEL PROVEEDOR
         */

        $validated = $request->validate([

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:suppliers,email,' . $supplier->id_supplier . ',id_supplier',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:20',
            ],

            'country' => [
                'required',
                'string',
                'max:100',
            ],

            'website' => [
                'nullable',
                'url',
                'max:255',
            ],


            /*
             * CONTACTOS
             */

            'contacts' => [
                'required',
                'array',
                'min:1',
            ],

            'contacts.*.id_contact' => [
                'nullable',
                'integer',
                'exists:supplier_contacts,id_contact',
            ],

            'contacts.*.full_name' => [
                'required',
                'string',
                'max:255',
            ],

            'contacts.*.phone' => [
                'required',
                'string',
                'max:20',
            ],

            'contacts.*.email' => [
                'nullable',
                'email',
                'max:255',
            ],
        ]);


        /*
         * TRANSACCIÓN
         *
         * Todo se guarda o nada se guarda.
         */

        DB::transaction(function () use ($validated, $supplier) {


            /*
             * 1. ACTUALIZAR DATOS DEL PROVEEDOR
             */

            $supplier->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'country' => $validated['country'],
                'website' => $validated['website'] ?? null,
            ]);


            /*
             * 2. OBTENER LOS ID DE CONTACTOS ENVIADOS
             *
             * Estos son los contactos que deben permanecer.
             */

            $contactIds = [];


            /*
             * 3. RECORRER CONTACTOS DEL FORMULARIO
             */

            foreach ($validated['contacts'] as $contactData) {


                /*
                 * CASO A:
                 * CONTACTO EXISTENTE
                 */

                if (!empty($contactData['id_contact'])) {

                    /*
                     * Buscamos el contacto SOLAMENTE dentro
                     * de este proveedor.
                     *
                     * Esto evita que un usuario pueda modificar
                     * accidentalmente el contacto de otro proveedor.
                     */

                    $contact = $supplier->contacts()
                        ->where(
                            'id_contact',
                            $contactData['id_contact']
                        )
                        ->first();


                    /*
                     * Si no pertenece a este proveedor,
                     * no permitimos actualizarlo.
                     */

                    if (!$contact) {

                        abort(
                            404,
                            'El contacto seleccionado no pertenece a este proveedor.'
                        );
                    }


                    /*
                     * Actualizar contacto existente.
                     */

                    $contact->full_name =
                        $contactData['full_name'];

                    $contact->phone =
                        $contactData['phone'];

                    $contact->email =
                        $contactData['email'] ?? null;

                    $contact->is_active = true;

                    $contact->save();


                    /*
                     * Guardamos su ID porque debe permanecer.
                     */

                    $contactIds[] = $contact->id_contact;
                }


                /*
                 * CASO B:
                 * CONTACTO NUEVO
                 */

                else {

                    $contact = new SupplierContact();

                    $contact->id_supplier =
                        $supplier->id_supplier;

                    $contact->full_name =
                        $contactData['full_name'];

                    $contact->phone =
                        $contactData['phone'];

                    $contact->email =
                        $contactData['email'] ?? null;

                    $contact->is_active = true;

                    $contact->save();


                    /*
                     * Guardamos el ID del nuevo contacto.
                     */

                    $contactIds[] =
                        $contact->id_contact;
                }
            }


            /*
             * 4. ELIMINAR CONTACTOS QUITADOS DEL FORMULARIO
             *
             * Si un contacto existía en la base de datos pero
             * ya no viene en el formulario, significa que el
             * usuario lo eliminó.
             */

            $supplier->contacts()
                ->whereNotIn(
                    'id_contact',
                    $contactIds
                )
                ->delete();
        });


        /*
             * MENSAJE DE ÉXITO
         */

        return redirect()
            ->route('suppliers.index')
            ->with(
                'success',
                'Proveedor y contactos actualizados correctamente.'
            );
    }

    /**
     * ELIMINAR PROVEEDOR
     */
    public function destroy(Supplier $supplier)
    {
        Gate::authorize('suppliers.eliminar');


        DB::transaction(function () use ($supplier) {

            /*
             * Primero eliminamos los contactos asociados.
             */

            $supplier->contacts()->delete();


            /*
             * Luego eliminamos el proveedor.
             */

            $supplier->delete();
        });


        return redirect()
            ->route('suppliers.index')
            ->with(
                'success',
                'Proveedor eliminado correctamente.'
            );
    }
}