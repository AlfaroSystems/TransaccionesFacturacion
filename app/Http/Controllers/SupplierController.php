<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SupplierController extends Controller
{

    public function index(Request $request)
   {
     Gate::authorize('suppliers.ver');

     $suppliers = Supplier::with('contacts')
        ->when($request->search, function ($query) use ($request) {

            $query->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('country', 'like', '%' . $request->search . '%')
                ->orWhereHas('contacts', function ($q) use ($request) {
                    $q->where('email', 'like', '%' . $request->search . '%');
                });

        })
        ->paginate(10)   
        ->withQueryString();

     return view('suppliers.index', compact('suppliers'));
    }



    public function create()
    {
        Gate::authorize('suppliers.crear');

        return view('suppliers.create');
    }



    public function store(Request $request)
    {
        Gate::authorize('suppliers.crear');


        $validated = $request->validate([

            'name'=>'required|string|max:255',

            'email'=>'required|email|unique:suppliers,email',

            'phone'=>'nullable|string|max:20',

            'country'=>'required|string|max:100',

            'website'=>'nullable|url|max:255',


            'contacts'=>'required|array',

            'contacts.*.full_name'=>'required|string|max:255',

            'contacts.*.phone'=>'required|string|max:20',

            'contacts.*.email'=>'nullable|email'

        ]);



        DB::transaction(function() use ($validated){


            $supplier = Supplier::create([

                'name'=>$validated['name'],

                'email'=>$validated['email'],

                'phone'=>$validated['phone'] ?? null,

                'country'=>$validated['country'],

                'website'=>$validated['website'] ?? null

            ]);



            foreach($validated['contacts'] as $contact)
            {

                SupplierContact::create([

                    'id_supplier'=>$supplier->id_supplier,

                    'full_name'=>$contact['full_name'],

                    'phone'=>$contact['phone'],

                    'email'=>$contact['email'] ?? null

                ]);

            }


        });



        return redirect()

        ->route('suppliers.index')

        ->with('success','Proveedor creado correctamente.');

    }





    public function show(Supplier $supplier)
    {
        Gate::authorize('suppliers.ver');


        $supplier->load('contacts');


        return view('suppliers.show',compact('supplier'));

    }





    public function edit(Supplier $supplier)
    {
        Gate::authorize('suppliers.editar');


        $supplier->load('contacts');


        return view('suppliers.edit',compact('supplier'));

    }





    public function update(Request $request, Supplier $supplier)
    {
        Gate::authorize('suppliers.editar');



        $validated = $request->validate([


            'name'=>'required|string|max:255',


            'email'=>'required|email|unique:suppliers,email,'.$supplier->id_supplier,


            'phone'=>'nullable|string|max:20',


            'country'=>'required|string|max:100',


            'website'=>'nullable|url|max:255',


            'contacts'=>'required|array',


            'contacts.*.full_name'=>'required|string|max:255',


            'contacts.*.phone'=>'required|string|max:20',


            'contacts.*.email'=>'nullable|email'


        ]);




        DB::transaction(function() use ($validated,$supplier){



            $supplier->update([

                'name'=>$validated['name'],

                'email'=>$validated['email'],

                'phone'=>$validated['phone'] ?? null,

                'country'=>$validated['country'],

                'website'=>$validated['website'] ?? null

            ]);



            $supplier->contacts()->delete();



            foreach($validated['contacts'] as $contact)
            {


                SupplierContact::create([

                    'id_supplier'=>$supplier->id_supplier,

                    'full_name'=>$contact['full_name'],

                    'phone'=>$contact['phone'],

                    'email'=>$contact['email'] ?? null

                ]);

            }


        });



        return redirect()

        ->route('suppliers.index')

        ->with('success','Proveedor actualizado correctamente.');

    }





    public function destroy(Supplier $supplier)
    {
        Gate::authorize('suppliers.eliminar');


        $supplier->delete();


        return redirect()

        ->route('suppliers.index')

        ->with('success','Proveedor eliminado correctamente.');

    }

}