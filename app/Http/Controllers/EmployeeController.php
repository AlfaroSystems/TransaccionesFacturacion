<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\InventoryTransfer;
use App\Models\Vehicle;
use App\Support\BranchAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Empleados de las sucursales: motoristas de los traslados y responsables de los vehículos.
 * Cada sucursal ve los suyos; el departamento de compras, los de toda su empresa.
 */
class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('employees.ver');

        $search = trim((string) $request->input('search'));
        $estado = in_array($request->input('estado'), ['activos', 'inactivos'], true) ? $request->input('estado') : null;

        $employees = Employee::query()
            ->with('branch')
            ->withCount('vehicles')
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('first_name', 'ilike', "%{$search}%")
                ->orWhere('last_name', 'ilike', "%{$search}%")
                ->orWhere('dui', 'ilike', "%{$search}%")
                ->orWhere('position', 'ilike', "%{$search}%")))
            ->when($estado, fn ($q) => $q->where('is_active', $estado === 'activos'))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate(20)
            ->withQueryString();

        return view('employees.index', compact('employees', 'search', 'estado'));
    }

    public function create()
    {
        Gate::authorize('employees.crear');

        return view('employees.form', ['employee' => null, 'branches' => $this->sucursales()]);
    }

    public function store(Request $request)
    {
        Gate::authorize('employees.crear');

        $employee = Employee::create($this->validar($request));

        return redirect()
            ->route('employees.index')
            ->with('success', "Empleado {$employee->fullName()} registrado.");
    }

    public function edit(Employee $employee)
    {
        Gate::authorize('employees.editar');

        return view('employees.form', ['employee' => $employee, 'branches' => $this->sucursales($employee)]);
    }

    public function update(Request $request, Employee $employee)
    {
        Gate::authorize('employees.editar');

        $employee->update($this->validar($request, $employee));

        return redirect()
            ->route('employees.index')
            ->with('success', "Empleado {$employee->fullName()} actualizado.");
    }

    /**
     * Solo se elimina si no es responsable de un vehículo ni manejó traslados; si no, se
     * desactiva desde su edición.
     */
    public function destroy(Employee $employee)
    {
        Gate::authorize('employees.eliminar');

        $enUso = Vehicle::where('id_responsible_employee', $employee->id_employee)->exists()
            || InventoryTransfer::queryAllBranches()->where('id_driver_employee', $employee->id_employee)->exists();

        if ($enUso) {
            return back()->with('error', "No se puede eliminar a {$employee->fullName()}: es responsable de un vehículo o motorista de traslados. Desactívelo.");
        }

        $nombre = $employee->fullName();
        $employee->delete();

        return redirect()
            ->route('employees.index')
            ->with('success', "Empleado {$nombre} eliminado.");
    }

    private function validar(Request $request, ?Employee $employee = null): array
    {
        $request->merge(['dui' => trim((string) $request->input('dui')) ?: null]);

        return $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'dui' => ['nullable', 'regex:/^\d{8}-\d$/', Rule::unique('employees', 'dui')->ignore($employee?->id_employee, 'id_employee')],
            'position' => ['nullable', 'string', 'max:100'],
            'id_branch' => ['required', 'integer', Rule::in($this->sucursales($employee)->pluck('id_branch')->all())],
            'is_active' => ['boolean'],
        ], [
            'dui.regex' => 'El DUI debe tener el formato 00000000-0.',
            'dui.unique' => 'Ya hay un empleado con ese DUI.',
            'id_branch.in' => 'Elija una sucursal de su empresa.',
        ]) + ['is_active' => $request->boolean('is_active')];
    }

    /**
     * Sucursales a las que puede pertenecer: la del usuario, o las de toda su empresa para el
     * departamento de compras (y la que ya tiene el empleado).
     */
    private function sucursales(?Employee $employee = null): Collection
    {
        $query = BranchAccess::isPurchasingDepartment()
            ? Branch::queryAllBranches()->whereIn('id_branch', BranchAccess::companyBranchIds())
            : Branch::query();

        return $query
            ->where(fn ($q) => $q->where('is_active', true)->when($employee?->id_branch, fn ($w, $id) => $w->orWhere('id_branch', $id)))
            ->orderBy('name')
            ->get();
    }
}
