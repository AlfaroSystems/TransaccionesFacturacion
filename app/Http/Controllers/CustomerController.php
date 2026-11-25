<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Department;
use App\Models\District;
use App\Models\Municipality;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('customers.ver');

        $search = $request->input('search');

        $customers = Customer::with(['department', 'municipality', 'district'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ilike', '%'.$search.'%')
                        ->orWhere('code', 'ilike', '%'.$search.'%')
                        ->orWhere('document_number', 'ilike', '%'.$search.'%')
                        ->orWhere('nrc', 'ilike', '%'.$search.'%')
                        ->orWhere('email', 'ilike', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $departments = Department::orderBy('name')->get();
        $municipalities = Municipality::orderBy('name')->get();
        $districts = District::orderBy('name')->get();

        return view('customers.index', compact('customers', 'departments', 'municipalities', 'districts'));
    }

    public function create()
    {
        Gate::authorize('customers.crear');

        // El formulario está en un modal del listado
        return redirect()->route('customers.index');
    }

    public function store(Request $request)
    {
        Gate::authorize('customers.crear');

        $validated = $this->validateCustomer($request);

        Customer::create($validated);

        return redirect()
            ->route('customers.index')
            ->with('success', 'Cliente creado correctamente.');
    }

    public function edit(Customer $customer)
    {
        Gate::authorize('customers.editar');

        // El formulario está en un modal del listado
        return redirect()->route('customers.index');
    }

    public function update(Request $request, Customer $customer)
    {
        Gate::authorize('customers.editar');

        $validated = $this->validateCustomer($request);

        $customer->update($validated);

        return redirect()
            ->route('customers.index')
            ->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Customer $customer)
    {
        Gate::authorize('customers.eliminar');

        $customer->update(['is_active' => ! $customer->is_active]);

        $message = $customer->is_active ? 'Cliente reactivado correctamente.' : 'Cliente desactivado correctamente.';

        return redirect()->route('customers.index')->with('success', $message);
    }

    /**
     * Valida y normaliza los datos del cliente. El contribuyente exige NRC y NIT; el código se
     * genera solo al crear. La ubicación geográfica solo aplica para El Salvador (si se indica).
     *
     * @return array<string, mixed>
     */
    private function validateCustomer(Request $request): array
    {
        $isTaxpayer = $request->input('customer_type') === Customer::TYPE_TAXPAYER;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'customer_type' => ['required', Rule::in([Customer::TYPE_FINAL, Customer::TYPE_TAXPAYER])],
            'document_type' => ['nullable', Rule::in(['dui', 'nit'])],
            'document_number' => 'nullable|string|max:50',
            'nrc' => [Rule::requiredIf($isTaxpayer), 'nullable', 'string', 'max:50'],
            'commercial_line' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'id_department' => 'nullable|exists:departments,id_department',
            'id_municipality' => 'nullable|exists:municipalities,id_municipality',
            'id_district' => 'nullable|exists:districts,id_district',
            'credit_limit' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ], [
            'nrc.required' => 'El NRC es obligatorio para un contribuyente.',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['id_department'] = $request->input('id_department') ?: null;
        $validated['id_municipality'] = $request->input('id_municipality') ?: null;
        $validated['id_district'] = $request->input('id_district') ?: null;

        return $validated;
    }
}
