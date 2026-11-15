<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\InventoryLocation;
use App\Models\InventoryLocationDetail;
use App\Models\InventoryMovement;
use App\Models\InventoryTransfer;
use App\Models\InventoryTransferDetail;
use App\Models\Vehicle;
use App\Models\VehicleLocation;
use App\Models\Warehouse;
use App\Support\BranchAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Vehículos de la empresa y sus espacios de carga (vehicle_locations). Cada espacio pertenece a
 * una bodega y es una ubicación de inventario. Los traslados registran el vehículo y su
 * motorista.
 */
class VehicleController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('vehicles.ver');

        $search = trim((string) $request->input('search'));

        $vehicles = Vehicle::query()
            ->with(['responsibleEmployee', 'locations.warehouseAllBranches'])
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('license_plate', 'ilike', "%{$search}%")
                ->orWhere('brand', 'ilike', "%{$search}%")
                ->orWhere('model', 'ilike', "%{$search}%")))
            ->orderBy('license_plate')
            ->paginate(20)
            ->withQueryString();

        return view('vehicles.index', compact('vehicles', 'search'));
    }

    public function create()
    {
        Gate::authorize('vehicles.crear');

        return view('vehicles.form', ['vehicle' => null, ...$this->datosDelFormulario()]);
    }

    public function store(Request $request)
    {
        Gate::authorize('vehicles.crear');

        [$datos, $espacios] = $this->validar($request);

        $vehicle = DB::transaction(function () use ($datos, $espacios) {
            $vehicle = Vehicle::create($datos);
            $this->guardarEspacios($vehicle, $espacios);

            return $vehicle;
        });

        return redirect()
            ->route('vehicles.index')
            ->with('success', "Vehículo {$vehicle->license_plate} registrado.");
    }

    public function edit(Vehicle $vehicle)
    {
        Gate::authorize('vehicles.editar');

        $vehicle->load('locations');

        return view('vehicles.form', ['vehicle' => $vehicle, ...$this->datosDelFormulario($vehicle)]);
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        Gate::authorize('vehicles.editar');

        [$datos, $espacios] = $this->validar($request, $vehicle);

        DB::transaction(function () use ($vehicle, $datos, $espacios) {
            $vehicle->update($datos);
            $this->guardarEspacios($vehicle, $espacios);
        });

        return redirect()
            ->route('vehicles.index')
            ->with('success', "Vehículo {$vehicle->license_plate} actualizado.");
    }

    /**
     * Solo se elimina si no tiene traslados ni movimientos en sus espacios; si no, se desactiva
     * desde su edición.
     */
    public function destroy(Vehicle $vehicle)
    {
        Gate::authorize('vehicles.eliminar');

        $espacios = VehicleLocation::queryAllBranches()->where('id_vehicle', $vehicle->id_vehicle)->pluck('id_vehicle_location');

        if (InventoryTransfer::queryAllBranches()->where('id_vehicle', $vehicle->id_vehicle)->exists() || $this->tieneHistorial($espacios)) {
            return back()->with('error', "No se puede eliminar el vehículo {$vehicle->license_plate}: ya tiene traslados o movimientos de inventario. Desactívelo.");
        }

        $placa = $vehicle->license_plate;

        DB::transaction(function () use ($vehicle) {
            // Uno por uno, para que cada borrado quede en la bitácora (su ubicación de
            // inventario se borra con él)
            VehicleLocation::queryAllBranches()->where('id_vehicle', $vehicle->id_vehicle)->get()->each->delete();
            $vehicle->delete();
        });

        return redirect()
            ->route('vehicles.index')
            ->with('success', "Vehículo {$placa} eliminado.");
    }

    /**
     * Datos del vehículo y sus espacios de carga (las filas con código).
     *
     * @return array{0: array, 1: array<int, array>}
     */
    private function validar(Request $request, ?Vehicle $vehicle = null): array
    {
        $request->merge([
            'license_plate' => mb_strtoupper(trim((string) $request->input('license_plate'))),
            // Las filas sin código no cuentan; las demás conservan su índice para mostrar sus errores
            'locations' => array_filter(
                (array) $request->input('locations', []),
                fn ($fila) => is_array($fila) && trim((string) ($fila['code'] ?? '')) !== ''
            ),
        ]);

        $validated = $request->validate([
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'min:1950', 'max:'.(now()->year + 1)],
            'license_plate' => ['required', 'string', 'max:20', Rule::unique('vehicles', 'license_plate')->ignore($vehicle?->id_vehicle, 'id_vehicle')],
            'id_responsible_employee' => ['nullable', 'integer', Rule::in($this->empleados($vehicle)->pluck('id_employee')->all())],
            'is_active' => ['boolean'],
            'locations' => ['nullable', 'array'],
            'locations.*.id_vehicle_location' => ['nullable', 'integer', Rule::in($vehicle ? $vehicle->locations()->pluck('id_vehicle_location')->all() : [])],
            'locations.*.id_warehouse' => ['required', 'integer', Rule::in($this->bodegas()->pluck('id_warehouse')->all())],
            'locations.*.code' => ['required', 'string', 'max:50'],
            'locations.*.capacity' => ['nullable', 'integer', 'min:0'],
            'locations.*.notes' => ['nullable', 'string', 'max:500'],
            'locations.*.is_active' => ['nullable', 'boolean'],
        ], [
            'license_plate.unique' => 'Ya hay un vehículo con esa placa.',
            'id_responsible_employee.in' => 'Elija un empleado activo.',
            'locations.*.id_warehouse.required' => 'Elija la bodega de este espacio.',
            'locations.*.id_warehouse.in' => 'Elija una bodega de su sucursal.',
            'locations.*.id_vehicle_location.in' => 'Ese espacio no es de este vehículo.',
        ]);

        $espacios = $validated['locations'] ?? [];
        $codigos = [];

        foreach ($espacios as $i => $espacio) {
            $codigo = mb_strtoupper(trim($espacio['code']));

            if (in_array($codigo, $codigos, true)) {
                throw ValidationException::withMessages(["locations.{$i}.code" => 'Ese código ya está en otro espacio del vehículo.']);
            }

            $codigos[] = $codigo;
        }

        unset($validated['locations']);

        return [$validated + ['is_active' => $request->boolean('is_active')], $espacios];
    }

    /**
     * Crea, actualiza y quita los espacios de carga visibles para el usuario. Un espacio con
     * movimientos o existencias no se quita: se desactiva.
     */
    private function guardarEspacios(Vehicle $vehicle, array $espacios): void
    {
        $actuales = $vehicle->locations()->get()->keyBy('id_vehicle_location');
        $enviados = collect($espacios)->pluck('id_vehicle_location')->filter()->map(fn ($id) => (int) $id);

        $quitados = $actuales->keys()->diff($enviados);
        $conHistorial = $quitados->filter(fn ($id) => $this->tieneHistorial(collect([$id])));

        if ($conHistorial->isNotEmpty()) {
            throw ValidationException::withMessages([
                'locations' => 'El espacio '.$actuales[$conHistorial->first()]->code.' ya tiene movimientos de inventario: no se puede quitar, desactívelo.',
            ]);
        }

        // Uno por uno, para que cada cambio quede en la bitácora
        $quitados->each(fn ($id) => $actuales[$id]->delete());

        foreach ($espacios as $espacio) {
            $datos = [
                'id_warehouse' => (int) $espacio['id_warehouse'],
                'code' => mb_strtoupper(trim($espacio['code'])),
                'capacity' => (int) ($espacio['capacity'] ?? 0),
                'notes' => $espacio['notes'] ?? null,
                'is_active' => (bool) ($espacio['is_active'] ?? false),
            ];

            if (! empty($espacio['id_vehicle_location'])) {
                $actuales[(int) $espacio['id_vehicle_location']]->update($datos);
            } else {
                $vehicle->locations()->create($datos);
            }
        }
    }

    /**
     * Algún espacio ya tiene movimientos, existencias o líneas de traslado.
     *
     * @param  Collection<int, int>  $espacios  id de vehicle_locations
     */
    private function tieneHistorial(Collection $espacios): bool
    {
        if ($espacios->isEmpty()) {
            return false;
        }

        $lugares = InventoryLocation::whereIn('id_vehicle_location', $espacios)->pluck('id_inventory_location');

        return InventoryMovement::queryAllBranches()->whereIn('id_inventory_location', $lugares)->exists()
            || InventoryLocationDetail::queryAllBranches()->whereIn('id_inventory_location', $lugares)->exists()
            || InventoryTransferDetail::query()->where(fn ($q) => $q
                ->whereIn('id_source_inventory_location', $lugares)
                ->orWhereIn('id_destination_inventory_location', $lugares))->exists();
    }

    private function datosDelFormulario(?Vehicle $vehicle = null): array
    {
        return [
            'empleados' => $this->empleados($vehicle),
            'bodegas' => $this->bodegas(),
        ];
    }

    /**
     * Empleados activos visibles (y el responsable que ya tiene el vehículo).
     */
    private function empleados(?Vehicle $vehicle = null): Collection
    {
        return Employee::query()
            ->with('branch')
            ->where(fn ($q) => $q->where('is_active', true)->when($vehicle?->id_responsible_employee, fn ($w, $id) => $w->orWhere('id_employee', $id)))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    /**
     * Bodegas a las que se asigna un espacio: las de la sucursal del usuario, o las de toda su
     * empresa para el departamento de compras.
     */
    private function bodegas(): Collection
    {
        $query = BranchAccess::isPurchasingDepartment()
            ? Warehouse::queryAllBranches()->whereIn('id_branch', BranchAccess::companyBranchIds())
            : Warehouse::query();

        return $query->with('branch')->where('is_active', true)->orderBy('name')->get();
    }
}
