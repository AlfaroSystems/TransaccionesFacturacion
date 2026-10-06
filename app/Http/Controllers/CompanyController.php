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
    public function index(Request $request)
    {
        Gate::authorize('companies.ver');

        $search = trim((string) $request->input('search', ''));

        $companies = Company::query()
            ->with([
                'department:id_department,name',
                'municipality:id_municipality,name',
                'district:id_district,name',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($companyQuery) use ($search) {
                    $companyQuery->where('name', 'ilike', "%{$search}%")
                        ->orWhere('commercial_name', 'ilike', "%{$search}%")
                        ->orWhere('nit', 'ilike', "%{$search}%")
                        ->orWhere('nrc', 'ilike', "%{$search}%")
                        ->orWhere('phone', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%");
                });
            })
            ->orderByDesc('id_company')
            ->paginate(10)
            ->withQueryString();

        // Cargar datos geográficos para los dropdowns de El Salvador.
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
            'addres' => 'nullable|string|max:500',
            'id_department' => 'nullable|exists:departments,id_department',
            'id_municipality' => 'nullable|exists:municipalities,id_municipality',
            'id_district' => 'nullable|exists:districts,id_district',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'web_site' => 'nullable|url|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_active' => 'boolean',
        ]);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('logos', 'public');
        }

        Company::create([
            'name' => $validated['name'],
            'commercial_name' => $validated['commercial_name'] ?? null,
            'nit' => $validated['nit'] ?? null,
            'nrc' => $validated['nrc'] ?? null,
            'commercial_line_1' => $validated['commercial_line_1'] ?? null,
            'commercial_line_2' => $validated['commercial_line_2'] ?? null,
            'commercial_line_3' => $validated['commercial_line_3'] ?? null,
            'addres' => $validated['addres'] ?? null,
            'id_department' => $validated['id_department'] ?? null,
            'id_municipality' => $validated['id_municipality'] ?? null,
            'id_district' => $validated['id_district'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'web_site' => $validated['web_site'] ?? null,
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
            'addres' => 'nullable|string|max:500',
            'id_department' => 'nullable|exists:departments,id_department',
            'id_municipality' => 'nullable|exists:municipalities,id_municipality',
            'id_district' => 'nullable|exists:districts,id_district',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'web_site' => 'nullable|url|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
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
            'addres' => $validated['addres'],
            'id_department' => $validated['id_department'],
            'id_municipality' => $validated['id_municipality'],
            'id_district' => $validated['id_district'],
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

        $newStatus = !$company->is_active;

        if (!$newStatus && $company->branches()->count() > 0) {
            return redirect()
                ->route('companies.index')
                ->with('error', 'No se puede inactivar la empresa porque tiene sucursales asociadas.');
        }

        $company->update([
            'is_active' => $newStatus,
        ]);

        $message = $newStatus ? 'Empresa reactivada correctamente.' : 'Empresa inactivada correctamente.';

        return redirect()
            ->route('companies.index')
            ->with('success', $message);
    }

    public function edit(Company $company)
    {
        Gate::authorize('companies.editar');

        // El formulario está en un modal del listado
        return redirect()->route('companies.index');
    }
}