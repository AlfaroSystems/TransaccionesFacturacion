<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('companies.ver');

        $companies = Company::orderBy('id', 'desc')->get();
        
        // Cargar datos geográficos para los dropdowns de El Salvador
        $departments = \App\Models\Department::orderBy('name')->get();
        $municipalities = \App\Models\Municipality::orderBy('name')->get();
        $districts = \App\Models\District::orderBy('name')->get();

        return view('companies.index', compact('companies', 'departments', 'municipalities', 'districts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('companies.crear');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'commercial_name' => 'nullable|string|max:255',
            'nit' => 'nullable|string|max:30',
            'nrc' => 'nullable|string|max:30',
            'commercial_line_1' => 'nullable|string|max:255',
            'commercial_line_2' => 'nullable|string|max:255',
            'commercial_line_3' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'department_id' => 'nullable|exists:departments,id',
            'municipality_id' => 'nullable|exists:municipalities,id',
            'district_id' => 'nullable|exists:districts,id',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'web_site' => 'nullable|url|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_active' => 'boolean',
        ]);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('logos', 'public');
        }

        Company::create([
            'name' => $validated['name'],
            'commercial_name' => $validated['commercial_name'],
            'nit' => $validated['nit'],
            'nrc' => $validated['nrc'],
            'commercial_line_1' => $validated['commercial_line_1'],
            'commercial_line_2' => $validated['commercial_line_2'],
            'commercial_line_3' => $validated['commercial_line_3'],
            'address' => $validated['address'],
            'department_id' => $validated['department_id'],
            'municipality_id' => $validated['municipality_id'],
            'district_id' => $validated['district_id'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'web_site' => $validated['web_site'],
            'logo' => $logoPath,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        return redirect()
            ->route('companies.index')
            ->with('success', 'Empresa registrada correctamente.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Company $company)
    {
        Gate::authorize('companies.editar');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'commercial_name' => 'nullable|string|max:255',
            'nit' => 'nullable|string|max:30',
            'nrc' => 'nullable|string|max:30',
            'commercial_line_1' => 'nullable|string|max:255',
            'commercial_line_2' => 'nullable|string|max:255',
            'commercial_line_3' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'department_id' => 'nullable|exists:departments,id',
            'municipality_id' => 'nullable|exists:municipalities,id',
            'district_id' => 'nullable|exists:districts,id',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'web_site' => 'nullable|url|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'is_active' => 'boolean',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'commercial_name' => $validated['commercial_name'],
            'nit' => $validated['nit'],
            'nrc' => $validated['nrc'],
            'commercial_line_1' => $validated['commercial_line_1'],
            'commercial_line_2' => $validated['commercial_line_2'],
            'commercial_line_3' => $validated['commercial_line_3'],
            'address' => $validated['address'],
            'department_id' => $validated['department_id'],
            'municipality_id' => $validated['municipality_id'],
            'district_id' => $validated['district_id'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'web_site' => $validated['web_site'],
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : false,
        ];

        if ($request->hasFile('logo')) {
            // Eliminar logo anterior si existe
            if ($company->logo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($company->logo);
            }
            $updateData['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $company->update($updateData);

        return redirect()
            ->route('companies.index')
            ->with('success', 'Empresa actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company)
    {
        Gate::authorize('companies.eliminar');

        // Validar si la empresa tiene sucursales antes de eliminar
        if ($company->branches()->count() > 0) {
            return redirect()
                ->route('companies.index')
                ->with('error', 'No se puede eliminar la empresa porque tiene sucursales asociadas.');
        }

        // Eliminar logo si existe
        if ($company->logo) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($company->logo);
        }

        $company->delete();

        return redirect()
            ->route('companies.index')
            ->with('success', 'Empresa eliminada correctamente.');
    }
}
