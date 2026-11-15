<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\InventoryLocation;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\InventoryTransfer;
use App\Models\Location;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleLocation;
use App\Models\Warehouse;
use App\Models\WarehouseCategory;
use Illuminate\Support\Str;

// =============================================================================
// Empleados, vehículos con sus espacios de carga, y ubicaciones de inventario: cada ubicación
// de bodega y cada espacio de vehículo tiene la suya.
// =============================================================================

function flotaUsuario(Branch $branch, array $permisos): User
{
    $role = Role::create(['name' => 'rol-'.Str::lower(Str::random(8))]);
    foreach ($permisos as $permiso) {
        Permission::firstOrCreate(['id_permission' => $permiso], ['name' => $permiso]);
    }
    $role->permissions()->sync($permisos);
    $user = User::factory()->create(['id_branch' => $branch->id_branch]);
    $user->roles()->attach($role->id_role, ['assigned_at' => now()]);

    return $user;
}

function flotaEscenario(): object
{
    $tag = Str::upper(Str::random(5));
    $company = Company::create(['name' => "Empresa {$tag}"]);
    $matriz = Branch::create(['id_company' => $company->id_company, 'name' => "Casa Matriz {$tag}", 'is_purchasing_department' => true]);
    $sucursal = Branch::create(['id_company' => $company->id_company, 'name' => "Sucursal {$tag}"]);
    $otra = Branch::create(['id_company' => Company::create(['name' => "Otra {$tag}"])->id_company, 'name' => "Ajena {$tag}"]);
    $category = WarehouseCategory::create(['name' => "Categoría {$tag}"]);
    $bodega = Warehouse::create(['id_branch' => $matriz->id_branch, 'id_warehouse_category' => $category->id_warehouse_category, 'name' => "Bodega {$tag}"]);
    $bodegaSucursal = Warehouse::create(['id_branch' => $sucursal->id_branch, 'id_warehouse_category' => $category->id_warehouse_category, 'name' => "Bodega Sucursal {$tag}"]);
    $permisos = ['employees.ver', 'employees.crear', 'employees.editar', 'employees.eliminar', 'vehicles.ver', 'vehicles.crear', 'vehicles.editar', 'vehicles.eliminar'];

    return (object) [
        'company' => $company, 'matriz' => $matriz, 'sucursal' => $sucursal, 'otra' => $otra,
        'bodega' => $bodega, 'bodegaSucursal' => $bodegaSucursal,
        'compras' => flotaUsuario($matriz, $permisos),
        'encargado' => flotaUsuario($sucursal, $permisos),
    ];
}

function flotaEmpleado(object $e, array $datos = []): array
{
    return $datos + [
        'first_name' => 'Ana', 'last_name' => 'López', 'dui' => '01234567-8', 'position' => 'Motorista',
        'phone' => '7777-7777', 'email' => 'ana@example.com', 'id_branch' => $e->matriz->id_branch, 'is_active' => '1',
    ];
}

test('cada ubicación de bodega tiene su ubicación de inventario, activa o no con ella', function () {
    $e = flotaEscenario();

    $location = Location::create(['id_warehouse' => $e->bodega->id_warehouse, 'code' => 'A1', 'capacity' => 10]);
    $lugar = InventoryLocation::where('id_location', $location->id_location)->sole();

    expect($lugar->location_type)->toBe(InventoryLocation::TYPE_LOCATION)
        ->and($lugar->id_vehicle_location)->toBeNull()
        ->and($lugar->is_active)->toBeTrue();

    $location->update(['is_active' => false]);
    expect($lugar->fresh()->is_active)->toBeFalse();
});

test('los empleados se registran con su sucursal y un DUI válido y único', function () {
    $e = flotaEscenario();
    $this->actingAs($e->compras);

    $this->post(route('employees.store'), flotaEmpleado($e, ['dui' => '123']))->assertSessionHasErrors(['dui' => 'El DUI debe tener el formato 00000000-0.']);
    // Una sucursal de otra empresa no se puede elegir
    $this->post(route('employees.store'), flotaEmpleado($e, ['id_branch' => $e->otra->id_branch]))->assertSessionHasErrors('id_branch');

    $this->post(route('employees.store'), flotaEmpleado($e))->assertSessionHasNoErrors()->assertRedirect(route('employees.index'));
    $this->post(route('employees.store'), flotaEmpleado($e, ['first_name' => 'Otra']))->assertSessionHasErrors(['dui' => 'Ya hay un empleado con ese DUI.']);
    // Casa Matriz registra también los de las sucursales de su empresa
    $this->post(route('employees.store'), flotaEmpleado($e, ['dui' => '98765432-1', 'first_name' => 'Luis', 'id_branch' => $e->sucursal->id_branch]))->assertSessionHasNoErrors();

    $ana = Employee::queryAllBranches()->where('first_name', 'Ana')->sole();
    expect($ana->fullName())->toBe('Ana López')->and($ana->is_active)->toBeTrue();

    // Se desactiva desde su edición
    $this->put(route('employees.update', $ana), flotaEmpleado($e, ['is_active' => '0']))->assertSessionHasNoErrors();
    expect($ana->fresh()->is_active)->toBeFalse();

    // Casa Matriz ve los de su empresa; la sucursal, solo los suyos
    $this->get(route('employees.index'))->assertOk()->assertSee('Ana López')->assertSee('Luis López');
    $this->actingAs($e->encargado)->get(route('employees.index'))->assertOk()->assertDontSee('Ana López')->assertSee('Luis López');
    $this->get(route('employees.edit', $ana))->assertNotFound();
});

test('un vehículo se registra con sus espacios de carga, y cada espacio es una ubicación de inventario', function () {
    $e = flotaEscenario();
    $this->actingAs($e->compras);
    $motorista = Employee::create(['first_name' => 'Carlos', 'last_name' => 'Ruiz', 'id_branch' => $e->matriz->id_branch]);

    $datos = [
        'license_plate' => ' c123-456 ', 'brand' => 'Isuzu', 'model' => 'NPR', 'year' => 2021,
        'id_responsible_employee' => $motorista->id_employee, 'is_active' => '1',
        'locations' => [
            ['code' => 'cajon-1', 'id_warehouse' => $e->bodega->id_warehouse, 'capacity' => 40, 'is_active' => '1'],
            ['code' => 'Cajon-1', 'id_warehouse' => $e->bodega->id_warehouse, 'capacity' => 10, 'is_active' => '1'],
        ],
    ];

    // Dos espacios con el mismo código no
    $this->post(route('vehicles.store'), $datos)->assertSessionHasErrors('locations.1.code');

    $datos['locations'][1]['code'] = 'PLATAFORMA';
    // Una fila sin código no cuenta
    $datos['locations'][2] = ['code' => '', 'id_warehouse' => ''];
    $this->post(route('vehicles.store'), $datos)->assertSessionHasNoErrors();

    $vehicle = Vehicle::sole();
    $espacios = VehicleLocation::queryAllBranches()->orderBy('code')->get();
    $lugares = InventoryLocation::whereIn('id_vehicle_location', $espacios->pluck('id_vehicle_location'))->get();

    expect($vehicle->license_plate)->toBe('C123-456')
        ->and($vehicle->label())->toBe('C123-456 · Isuzu NPR')
        ->and($espacios->pluck('code')->all())->toBe(['CAJON-1', 'PLATAFORMA'])
        ->and($lugares)->toHaveCount(2)
        ->and($lugares->pluck('location_type')->unique()->all())->toBe([InventoryLocation::TYPE_VEHICLE]);

    // La placa es única
    $this->post(route('vehicles.store'), ['license_plate' => 'C123-456', 'brand' => 'Hino', 'model' => '300'])->assertSessionHasErrors('license_plate');

    // Editar: se desactiva un espacio (y su ubicación de inventario) y se quita el otro
    $cajon = $espacios->firstWhere('code', 'CAJON-1');
    $this->put(route('vehicles.update', $vehicle), [
        'license_plate' => 'C123-456', 'brand' => 'Isuzu', 'model' => 'NPR', 'is_active' => '1',
        'locations' => [['id_vehicle_location' => $cajon->id_vehicle_location, 'code' => 'CAJON-1', 'id_warehouse' => $e->bodega->id_warehouse, 'is_active' => '0']],
    ])->assertSessionHasNoErrors();

    expect(VehicleLocation::queryAllBranches()->pluck('code')->all())->toBe(['CAJON-1'])
        ->and($cajon->fresh()->is_active)->toBeFalse()
        ->and(InventoryLocation::where('id_vehicle_location', $cajon->id_vehicle_location)->sole()->is_active)->toBeFalse()
        ->and(InventoryLocation::where('location_type', InventoryLocation::TYPE_VEHICLE)->count())->toBe(1);

    $this->get(route('vehicles.index'))->assertOk()->assertSee('C123-456')->assertSee('CAJON-1');
    $this->get(route('vehicles.edit', $vehicle))->assertOk()->assertSee('CAJON-1');

    // Un espacio no se asigna a una bodega de otra empresa
    $ajena = Warehouse::create(['id_branch' => $e->otra->id_branch, 'id_warehouse_category' => $e->bodega->id_warehouse_category, 'name' => 'Ajena']);
    $this->put(route('vehicles.update', $vehicle), [
        'license_plate' => 'C123-456', 'brand' => 'Isuzu', 'model' => 'NPR',
        'locations' => [['code' => 'X', 'id_warehouse' => $ajena->id_warehouse]],
    ])->assertSessionHasErrors('locations.0.id_warehouse');
});

test('el traslado registra su vehículo y su motorista', function () {
    $e = flotaEscenario();
    $motorista = Employee::create(['first_name' => 'Carlos', 'last_name' => 'Ruiz', 'id_branch' => $e->matriz->id_branch]);
    $vehicle = Vehicle::create(['license_plate' => 'C1', 'brand' => 'Isuzu', 'model' => 'NPR']);
    $inactivo = Vehicle::create(['license_plate' => 'C2', 'brand' => 'Hino', 'model' => '300', 'is_active' => false]);
    $a1 = Location::create(['id_warehouse' => $e->bodega->id_warehouse, 'code' => 'A1', 'capacity' => 0]);
    $product = Product::create(['name' => 'Laptop', 'sku' => 'L-'.Str::random(5)]);
    $lot = InventoryLot::create([
        'lot_code' => 'LOT-'.Str::random(5), 'id_product' => $product->id_product, 'id_warehouse' => $e->bodega->id_warehouse, 'received_at' => now(),
        'quantity_received' => 5, 'unit_cost' => 2, 'total_cost' => 10, 'status' => InventoryLot::STATUS_ACTIVE, 'placed_at' => now(),
    ]);
    InventoryMovement::create([
        'id_product' => $product->id_product, 'id_inventory_lot' => $lot->id_inventory_lot, 'id_warehouse' => $e->bodega->id_warehouse,
        'id_inventory_location' => ubicacionInventario($a1), 'movement_type' => 'purchase_receipt', 'movement_date' => now(),
        'quantity_in' => 5, 'quantity_out' => 0, 'unit_cost' => 2, 'total_cost' => 10,
    ]);

    $this->actingAs(flotaUsuario($e->matriz, ['inventory.ver', 'inventory.trasladar', 'inventory.recibir_traslados']));
    $datos = fn (array $extra) => $extra + [
        'id_source_warehouse' => $e->bodega->id_warehouse, 'id_destination_warehouse' => $e->bodegaSucursal->id_warehouse,
        'transfer_date' => now()->toDateString(),
        'details' => [['id_inventory_lot' => $lot->id_inventory_lot, 'id_source_inventory_location' => ubicacionInventario($a1), 'quantity_requested' => 2]],
    ];

    $this->post(route('inventory-transfers.store'), $datos(['id_vehicle' => $inactivo->id_vehicle]))->assertSessionHasErrors(['id_vehicle' => 'Elija un vehículo activo.']);
    $this->post(route('inventory-transfers.store'), $datos(['id_vehicle' => $vehicle->id_vehicle, 'id_driver_employee' => $motorista->id_employee]))->assertSessionHasNoErrors();

    $transfer = InventoryTransfer::queryAllBranches()->sole();
    expect($transfer->id_vehicle)->toBe($vehicle->id_vehicle)->and($transfer->id_driver_employee)->toBe($motorista->id_employee);
    $this->get(route('inventory-transfers.show', $transfer))->assertOk()->assertSee('C1 · Isuzu NPR')->assertSee('Carlos Ruiz');

    // Sin ubicación destino en el borrador, se elige al recibir
    $this->post(route('inventory-transfers.dispatch', $transfer))->assertSessionHas('success');
    $detalle = $transfer->details()->sole();
    $this->post(route('inventory-transfers.receive', $transfer))->assertSessionHasErrors("received.{$detalle->id_inventory_transfer_detail}.id_destination_inventory_location");
    expect($transfer->fresh()->status)->toBe('dispatched');

    $s1 = Location::create(['id_warehouse' => $e->bodegaSucursal->id_warehouse, 'code' => 'S1', 'capacity' => 0]);
    $this->post(route('inventory-transfers.receive', $transfer), [
        'received' => [$detalle->id_inventory_transfer_detail => ['id_destination_inventory_location' => ubicacionInventario($s1)]],
    ])->assertSessionHas('success');
    expect($transfer->fresh()->status)->toBe('received')
        ->and(existenciaPorUbicacion()[0])->toBe([
            $lot->id_inventory_lot.'|'.ubicacionInventario($a1) => 3.0,
            $lot->id_inventory_lot.'|'.ubicacionInventario($s1) => 2.0,
        ]);

    // Con traslados, el vehículo y el motorista ya no se eliminan
    $this->actingAs($e->compras);
    $this->delete(route('vehicles.destroy', $vehicle))->assertSessionHas('error');
    $this->delete(route('employees.destroy', $motorista))->assertSessionHas('error');
    expect(Vehicle::whereKey($vehicle->id_vehicle)->exists())->toBeTrue()
        ->and(Employee::queryAllBranches()->whereKey($motorista->id_employee)->exists())->toBeTrue();

    // Sin uso, sí
    $this->delete(route('vehicles.destroy', $inactivo))->assertSessionHas('success');
    expect(Vehicle::whereKey($inactivo->id_vehicle)->exists())->toBeFalse();
});

test('empleados y vehículos requieren su permiso', function () {
    $e = flotaEscenario();
    $this->actingAs(flotaUsuario($e->matriz, ['inventory.ver']));

    $this->get(route('employees.index'))->assertForbidden();
    $this->get(route('vehicles.index'))->assertForbidden();
    $this->post(route('employees.store'), flotaEmpleado($e))->assertForbidden();
    $this->post(route('vehicles.store'), ['license_plate' => 'X1', 'brand' => 'A', 'model' => 'B'])->assertForbidden();

    // Solo ver: no se crea
    $this->actingAs(flotaUsuario($e->matriz, ['employees.ver', 'vehicles.ver']));
    $this->get(route('employees.index'))->assertOk()->assertDontSee('Nuevo Empleado');
    $this->get(route('vehicles.create'))->assertForbidden();
});
