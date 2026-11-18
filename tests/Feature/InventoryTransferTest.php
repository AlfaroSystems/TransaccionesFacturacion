<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\InventoryCostAverage;
use App\Models\InventoryLabelPrint;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\InventoryTransfer;
use App\Models\InventoryTransferDiscrepancy;
use App\Models\Location;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleLocation;
use App\Models\Warehouse;
use App\Models\WarehouseCategory;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// =============================================================================
// Traslados: se crean con vehículo y motorista, se autorizan, se preparan, viajan en el
// espacio de carga del vehículo y se reciben (de una vez o por partes). Lo que no llega se
// registra como faltante o daño y lo que llega de más como sobrante; al regularizar se cierra.
// Y etiquetas de lote, con motivo en cada reimpresión.
// =============================================================================

function trasladoUsuario(Branch $branch, array $permisos): User
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

/** Casa Matriz con un lote de 10 a $5 en A1, como lo deja una compra recibida, y un camión con su motorista */
function trasladoEscenario(): object
{
    $tag = Str::upper(Str::random(5));
    $company = Company::create(['name' => "Empresa {$tag}"]);
    $matriz = Branch::create(['id_company' => $company->id_company, 'name' => "Casa Matriz {$tag}", 'is_purchasing_department' => true]);
    $sucursal = Branch::create(['id_company' => $company->id_company, 'name' => "Sucursal {$tag}"]);
    $category = WarehouseCategory::create(['name' => "Categoría {$tag}"]);
    $bodega = fn (Branch $branch, string $name) => Warehouse::create(['id_branch' => $branch->id_branch, 'id_warehouse_category' => $category->id_warehouse_category, 'name' => "{$name} {$tag}"]);
    $bodegaMatriz = $bodega($matriz, 'Bodega Principal');
    $bodegaSucursal = $bodega($sucursal, 'Bodega Sucursal');
    $a1 = Location::create(['id_warehouse' => $bodegaMatriz->id_warehouse, 'code' => 'A1', 'capacity' => 100]);
    $s1 = Location::create(['id_warehouse' => $bodegaSucursal->id_warehouse, 'code' => 'S1', 'capacity' => 100]);
    $product = Product::create(['name' => "Laptop {$tag}", 'sku' => 'L-'.$tag]);

    $lot = InventoryLot::create([
        'lot_code' => 'LOT-'.$tag, 'id_product' => $product->id_product, 'id_warehouse' => $bodegaMatriz->id_warehouse, 'received_at' => now(),
        'quantity_received' => 10, 'unit_cost' => 5, 'total_cost' => 50, 'status' => InventoryLot::STATUS_ACTIVE, 'placed_at' => now(),
    ]);
    InventoryMovement::create([
        'id_product' => $product->id_product, 'id_inventory_lot' => $lot->id_inventory_lot, 'id_warehouse' => $bodegaMatriz->id_warehouse,
        'id_inventory_location' => ubicacionInventario($a1), 'movement_type' => InventoryMovement::TYPE_PURCHASE_RECEIPT, 'movement_date' => now(),
        'quantity_in' => 10, 'quantity_out' => 0, 'unit_cost' => 5, 'total_cost' => 50,
    ]);
    InventoryCostAverage::create(['id_company' => $company->id_company, 'id_product' => $product->id_product, 'quantity_on_hand' => 10, 'inventory_value' => 50, 'average_unit_cost' => 5]);

    $motorista = Employee::create(['first_name' => 'Carlos', 'last_name' => 'Ruiz', 'position' => 'Motorista', 'id_branch' => $matriz->id_branch]);
    $camion = Vehicle::create(['license_plate' => 'C-'.$tag, 'brand' => 'Isuzu', 'model' => 'NPR', 'id_responsible_employee' => $motorista->id_employee]);
    $cajon = $camion->locations()->create(['id_warehouse' => $bodegaMatriz->id_warehouse, 'code' => 'CAJON-1', 'capacity' => 0]);

    return (object) [
        'company' => $company, 'matriz' => $matriz, 'sucursal' => $sucursal,
        'bodegaMatriz' => $bodegaMatriz, 'bodegaSucursal' => $bodegaSucursal, 'a1' => $a1, 's1' => $s1,
        'product' => $product, 'lot' => $lot, 'motorista' => $motorista, 'camion' => $camion, 'cajon' => $cajon,
        'compras' => trasladoUsuario($matriz, ['inventory.ver', 'inventory.trasladar', 'inventory.etiquetas']),
        'autorizador' => trasladoUsuario($matriz, ['inventory.ver', 'inventory.autorizar_traslados', 'inventory.regularizar_traslados']),
        'receptor' => trasladoUsuario($sucursal, ['inventory.ver', 'inventory.recibir_traslados']),
    ];
}

function trasladoDatos(object $e, float $cantidad, bool $conTransporte = true): array
{
    return [
        'id_source_warehouse' => $e->bodegaMatriz->id_warehouse,
        'id_destination_warehouse' => $e->bodegaSucursal->id_warehouse,
        'transfer_date' => now()->toDateString(),
        'details' => [[
            'id_inventory_lot' => $e->lot->id_inventory_lot, 'id_source_inventory_location' => ubicacionInventario($e->a1),
            'quantity_requested' => $cantidad, 'id_destination_inventory_location' => ubicacionInventario($e->s1),
        ]],
    ] + ($conTransporte ? ['id_vehicle' => $e->camion->id_vehicle, 'id_driver_employee' => $e->motorista->id_employee] : []);
}

/** Crea el traslado y lo lleva por autorización y preparación hasta despacharlo (lo preparado: todo lo solicitado) */
function trasladoDespachado(object $e, float $cantidad): InventoryTransfer
{
    test()->actingAs($e->compras)->post(route('inventory-transfers.store'), trasladoDatos($e, $cantidad))->assertSessionHasNoErrors();
    $transfer = InventoryTransfer::queryAllBranches()->latest('id_inventory_transfer')->first();

    test()->post(route('inventory-transfers.submit', $transfer))->assertSessionHas('success');
    test()->actingAs($e->autorizador)->post(route('inventory-transfers.authorize', $transfer))->assertSessionHas('success');
    test()->actingAs($e->compras)->post(route('inventory-transfers.prepare', $transfer))->assertSessionHas('success');
    test()->post(route('inventory-transfers.dispatch', $transfer), ['id_vehicle_location' => $e->cajon->id_vehicle_location])->assertSessionHas('success');

    return $transfer->fresh();
}

/** Existencia del lote en cada ubicación ("A1", "S1", "CAJON-1"), según inventory_location_details */
function trasladoUbicaciones(object $e): array
{
    [$detalle, $movimientos] = existenciaPorUbicacion();
    expect($detalle)->toBe($movimientos);

    $nombres = [
        $e->lot->id_inventory_lot.'|'.ubicacionInventario($e->a1) => 'A1',
        $e->lot->id_inventory_lot.'|'.ubicacionInventario($e->s1) => 'S1',
        $e->lot->id_inventory_lot.'|'.$e->cajon->inventoryLocation()->value('id_inventory_location') => 'CAJON-1',
    ];

    return collect($detalle)->mapWithKeys(fn ($cantidad, $clave) => [$nombres[$clave] ?? $clave => $cantidad])->sortKeys()->all();
}

function trasladoPromedio(object $e): InventoryCostAverage
{
    return InventoryCostAverage::queryAllBranches()->where('id_company', $e->company->id_company)->where('id_product', $e->product->id_product)->sole();
}

test('el traslado completo: se autoriza, se prepara, viaja en el vehículo y se recibe', function () {
    $e = trasladoEscenario();

    $this->actingAs($e->compras)->post(route('inventory-transfers.store'), trasladoDatos($e, 6))->assertSessionHasNoErrors();
    $transfer = InventoryTransfer::queryAllBranches()->sole();

    // El borrador no mueve nada: guarda lo solicitado
    expect($transfer->status)->toBe(InventoryTransfer::STATUS_DRAFT)
        ->and($transfer->transfer_code)->toStartWith('TRF-')
        ->and((float) $transfer->details()->sole()->quantity_requested)->toBe(6.0)
        ->and(trasladoUbicaciones($e))->toBe(['A1' => 10.0]);

    // Se envía a autorizar; quien crea traslados no autoriza
    $this->post(route('inventory-transfers.submit', $transfer))->assertSessionHas('success');
    expect($transfer->fresh()->status)->toBe(InventoryTransfer::STATUS_PENDING_AUTHORIZATION);
    $this->post(route('inventory-transfers.authorize', $transfer))->assertForbidden();
    $this->get(route('inventory-transfers.edit', $transfer))->assertRedirect(route('inventory-transfers.show', $transfer));

    $this->actingAs($e->autorizador)->post(route('inventory-transfers.authorize', $transfer))->assertSessionHas('success');
    $transfer->refresh();
    expect($transfer->status)->toBe(InventoryTransfer::STATUS_AUTHORIZED)
        ->and($transfer->id_authorized_by)->toBe($e->autorizador->id_user)
        ->and($transfer->authorized_at)->not->toBeNull();

    // Se prepara con su lista y se despacha al espacio del camión
    $this->actingAs($e->compras)->post(route('inventory-transfers.prepare', $transfer))->assertSessionHas('success');
    $this->get(route('inventory-transfers.picking', $transfer))->assertOk()->assertSee('Lista de preparación')->assertSee('A1')->assertSee($e->lot->lot_code);
    $this->get(route('inventory-transfers.show', $transfer))->assertOk()->assertSee('Confirmar despacho')->assertSee('CAJON-1');

    $this->post(route('inventory-transfers.dispatch', $transfer), ['id_vehicle_location' => $e->cajon->id_vehicle_location])->assertSessionHas('success');
    $transfer->refresh();
    expect($transfer->status)->toBe(InventoryTransfer::STATUS_IN_TRANSIT)
        ->and($transfer->id_vehicle_location)->toBe($e->cajon->id_vehicle_location)
        ->and((float) $transfer->details()->sole()->quantity_dispatched)->toBe(6.0)
        ->and(trasladoUbicaciones($e))->toBe(['A1' => 4.0, 'CAJON-1' => 6.0]);

    // La sucursal verifica y recibe todo en S1
    $this->actingAs($e->receptor)->get(route('inventory-transfers.show', $transfer))->assertOk()->assertSee('Verificar y confirmar recepción');
    $detalle = $transfer->details()->sole();
    $this->post(route('inventory-transfers.receive', $transfer), [
        'received' => [$detalle->id_inventory_transfer_detail => ['quantity_received' => 6, 'id_destination_inventory_location' => ubicacionInventario($e->s1)]],
    ])->assertSessionHas('success', 'Traslado recibido completo: su mercadería ya está en la bodega destino.');

    $promedio = trasladoPromedio($e);
    expect($transfer->fresh()->status)->toBe(InventoryTransfer::STATUS_RECEIVED)
        ->and($transfer->fresh()->received_at)->not->toBeNull()
        ->and(trasladoUbicaciones($e))->toBe(['A1' => 4.0, 'S1' => 6.0])
        ->and(InventoryMovement::queryAllBranches()->where('reference_type', 'transfer')->orderBy('id_inventory_movement')->pluck('movement_type')->all())
        ->toBe(['transfer_out', 'transfer_load', 'transfer_unload', 'transfer_in'])
        // Sigue en la empresa: el promedio no cambia
        ->and((float) $promedio->quantity_on_hand)->toBe(10.0)
        ->and((float) $promedio->inventory_value)->toBe(50.0);

    $this->get(route('inventory.index'))->assertOk()->assertSee($e->lot->lot_code);
    $this->get(route('inventory-transfers.show', $transfer))->assertOk()
        ->assertSee('Seguimiento')->assertSee('Autorizado')->assertSee('Despachado: en tránsito')->assertSee('Recepción de 6 unidades');
});

test('sin vehículo o motorista no se envía a autorizar; rechazado vuelve a borrador con su motivo', function () {
    $e = trasladoEscenario();
    $this->actingAs($e->compras)->post(route('inventory-transfers.store'), trasladoDatos($e, 3, conTransporte: false))->assertSessionHasNoErrors();
    $transfer = InventoryTransfer::queryAllBranches()->sole();

    $this->post(route('inventory-transfers.submit', $transfer))->assertSessionHas('error', 'Asigne un vehículo activo al traslado (edite el borrador).');

    // Un vehículo sin espacios de carga activos no sirve
    $e->cajon->update(['is_active' => false]);
    $this->put(route('inventory-transfers.update', $transfer), trasladoDatos($e, 3))->assertSessionHasNoErrors();
    $this->post(route('inventory-transfers.submit', $transfer))->assertSessionHas('error');
    expect($transfer->fresh()->status)->toBe(InventoryTransfer::STATUS_DRAFT);

    $e->cajon->update(['is_active' => true]);
    $this->post(route('inventory-transfers.submit', $transfer))->assertSessionHas('success');

    // Rechazar exige el motivo; vuelve a borrador para corregirlo
    $this->actingAs($e->autorizador)->post(route('inventory-transfers.reject', $transfer))->assertSessionHasErrors('status_reason');
    $this->post(route('inventory-transfers.reject', $transfer), ['status_reason' => 'Mandar solo 2'])->assertSessionHas('success');
    expect($transfer->fresh()->status)->toBe(InventoryTransfer::STATUS_DRAFT)
        ->and($transfer->fresh()->status_reason)->toBe('Mandar solo 2');

    // El seguimiento muestra el envío y el rechazo, quién lo rechazó y por qué
    $this->actingAs($e->compras)->get(route('inventory-transfers.show', $transfer))->assertOk()->assertSee('Mandar solo 2')
        ->assertSeeInOrder(['Enviado a autorización', 'Rechazado: volvió a borrador', $e->autorizador->username.' · Mandar solo 2']);
    $this->put(route('inventory-transfers.update', $transfer), trasladoDatos($e, 2))->assertSessionHasNoErrors();
    $this->post(route('inventory-transfers.submit', $transfer))->assertSessionHas('success');
    $this->actingAs($e->autorizador)->post(route('inventory-transfers.authorize', $transfer))->assertSessionHas('success');

    expect($transfer->fresh()->status)->toBe(InventoryTransfer::STATUS_AUTHORIZED)
        ->and($transfer->fresh()->status_reason)->toBeNull()
        ->and((float) $transfer->details()->sole()->quantity_requested)->toBe(2.0);
});

test('se despacha lo preparado, que debe existir en la bodega y caber en el espacio del vehículo', function () {
    $e = trasladoEscenario();
    $this->actingAs($e->compras);

    // No se solicita más de lo que hay
    $this->post(route('inventory-transfers.store'), trasladoDatos($e, 12))->assertSessionHasErrors('details.0.quantity_requested');

    $this->post(route('inventory-transfers.store'), trasladoDatos($e, 6));
    $transfer = InventoryTransfer::queryAllBranches()->sole();
    $this->post(route('inventory-transfers.submit', $transfer));
    $this->actingAs($e->autorizador)->post(route('inventory-transfers.authorize', $transfer));
    $this->actingAs($e->compras)->post(route('inventory-transfers.prepare', $transfer));
    $linea = $transfer->details()->sole()->id_inventory_transfer_detail;
    $despachar = fn (array $datos) => $this->post(route('inventory-transfers.dispatch', $transfer), $datos + ['id_vehicle_location' => $e->cajon->id_vehicle_location]);

    $despachar(['prepared' => [$linea => 7]])->assertSessionHasErrors("prepared.{$linea}");
    $despachar(['prepared' => [$linea => 0]])->assertSessionHasErrors('prepared');

    // El espacio de otro vehículo no
    $otro = Vehicle::create(['license_plate' => 'X-1', 'brand' => 'Hino', 'model' => '300'])
        ->locations()->create(['id_warehouse' => $e->bodegaMatriz->id_warehouse, 'code' => 'OTRO']);
    $this->post(route('inventory-transfers.dispatch', $transfer), ['id_vehicle_location' => $otro->id_vehicle_location])->assertSessionHasErrors('id_vehicle_location');

    // Que quepa en el espacio
    $e->cajon->update(['capacity' => 3]);
    $despachar(['prepared' => [$linea => 4]])->assertSessionHasErrors(['id_vehicle_location' => 'El espacio CAJON-1 tiene capacidad para 3 y ya lleva 0: caben 3 más.']);

    // Se preparó menos de lo solicitado
    $e->cajon->update(['capacity' => 0]);
    $despachar(['prepared' => [$linea => 4]])->assertSessionHas('success');
    expect((float) $transfer->details()->sole()->quantity_dispatched)->toBe(4.0)
        ->and(trasladoUbicaciones($e))->toBe(['A1' => 6.0, 'CAJON-1' => 4.0]);
});

test('dos traslados no despachan la misma existencia', function () {
    $e = trasladoEscenario();
    $this->actingAs($e->compras);

    // Los dos caben al enviarlos, pero solo uno se puede despachar
    foreach ([1, 2] as $vez) {
        $this->post(route('inventory-transfers.store'), trasladoDatos($e, 6));
    }
    [$primero, $segundo] = InventoryTransfer::queryAllBranches()->orderBy('id_inventory_transfer')->get();

    foreach ([$primero, $segundo] as $transfer) {
        $this->actingAs($e->compras)->post(route('inventory-transfers.submit', $transfer))->assertSessionHas('success');
        $this->actingAs($e->autorizador)->post(route('inventory-transfers.authorize', $transfer))->assertSessionHas('success');
        $this->actingAs($e->compras)->post(route('inventory-transfers.prepare', $transfer))->assertSessionHas('success');
    }

    $this->post(route('inventory-transfers.dispatch', $primero), ['id_vehicle_location' => $e->cajon->id_vehicle_location])->assertSessionHas('success');
    $this->post(route('inventory-transfers.dispatch', $segundo), ['id_vehicle_location' => $e->cajon->id_vehicle_location])
        ->assertSessionHas('error', "No se puede despachar: Del lote {$e->lot->lot_code} solo hay 4 en esa ubicación de la bodega origen.");

    expect($segundo->fresh()->status)->toBe(InventoryTransfer::STATUS_PREPARING)
        ->and(trasladoUbicaciones($e))->toBe(['A1' => 4.0, 'CAJON-1' => 6.0]);
});

test('recepción parcial: lo que falta sigue en el vehículo hasta que llega', function () {
    $e = trasladoEscenario();
    $transfer = trasladoDespachado($e, 6);
    $linea = $transfer->details()->sole()->id_inventory_transfer_detail;

    $this->actingAs($e->receptor);
    // Sin ubicación destino (ni en el borrador ni al recibir) no se recibe
    $transfer->details()->update(['id_destination_inventory_location' => null]);
    $this->post(route('inventory-transfers.receive', $transfer), ['received' => [$linea => ['quantity_received' => 1]]])
        ->assertSessionHasErrors("received.{$linea}.id_destination_inventory_location");

    expect($transfer->fresh()->status)->toBe(InventoryTransfer::STATUS_IN_TRANSIT)
        ->and(trasladoUbicaciones($e))->toBe(['A1' => 4.0, 'CAJON-1' => 6.0]);

    $this->post(route('inventory-transfers.receive', $transfer), [
        'received' => [$linea => ['quantity_received' => 4, 'id_destination_inventory_location' => ubicacionInventario($e->s1)]],
    ])->assertSessionHas('success', 'Recepción parcial registrada: lo que falta sigue en el vehículo.');

    expect($transfer->fresh()->status)->toBe(InventoryTransfer::STATUS_PARTIALLY_RECEIVED)
        ->and(trasladoUbicaciones($e))->toBe(['A1' => 4.0, 'CAJON-1' => 2.0, 'S1' => 4.0]);

    // El resto llega después: vuelve a tránsito y se recibe (sin cantidad, todo lo pendiente)
    $this->post(route('inventory-transfers.continue-transit', $transfer))->assertSessionHas('success');
    expect($transfer->fresh()->status)->toBe(InventoryTransfer::STATUS_IN_TRANSIT);
    $this->get(route('inventory-transfers.show', $transfer))->assertOk()->assertSeeInOrder(['Recepción de 4 unidades', 'Sigue en tránsito']);

    $this->post(route('inventory-transfers.receive', $transfer))->assertSessionHas('success');
    expect($transfer->fresh()->status)->toBe(InventoryTransfer::STATUS_RECEIVED)
        ->and((float) $transfer->details()->sole()->quantity_received)->toBe(6.0)
        ->and(trasladoUbicaciones($e))->toBe(['A1' => 4.0, 'S1' => 6.0]);
});

test('faltantes y daños salen del vehículo como pérdida al regularizar', function () {
    $e = trasladoEscenario();
    $transfer = trasladoDespachado($e, 6);
    $linea = $transfer->details()->sole()->id_inventory_transfer_detail;

    $this->actingAs($e->receptor)->post(route('inventory-transfers.receive', $transfer), [
        'received' => [$linea => ['quantity_received' => 3, 'id_destination_inventory_location' => ubicacionInventario($e->s1)]],
    ]);

    $this->get(route('inventory-transfers.discrepancies', $transfer))->assertOk()->assertSee('Registrar discrepancias');
    $registrar = fn (array $datos) => $this->post(route('inventory-transfers.discrepancies.store', $transfer), ['discrepancies' => [$linea => $datos]]);

    // Faltante y daño no pasan de lo que sigue en el vehículo (3)
    $registrar(['shortage' => 2, 'damage' => 2])->assertSessionHasErrors("discrepancies.{$linea}.shortage");
    $registrar([])->assertSessionHasErrors('discrepancies');

    // Registradas de menos: no se regulariza hasta cubrir lo que sigue en el vehículo
    $registrar(['shortage' => 1, 'notes' => 'Una caja no venía'])->assertSessionHasNoErrors();
    expect($transfer->fresh()->status)->toBe(InventoryTransfer::STATUS_WITH_DISCREPANCY);

    $this->post(route('inventory-transfers.regularize', $transfer))->assertForbidden();
    $this->actingAs($e->autorizador)->post(route('inventory-transfers.regularize', $transfer))->assertSessionHas('error');

    // Corregidas: 2 faltan y 1 llegó dañada
    $this->actingAs($e->receptor);
    $registrar(['shortage' => 2, 'damage' => 1, 'notes' => 'Una caja no venía'])->assertSessionHasNoErrors();
    expect(InventoryTransferDiscrepancy::count())->toBe(2);

    $this->actingAs($e->autorizador)->post(route('inventory-transfers.regularize', $transfer))->assertSessionHas('success');

    $promedio = trasladoPromedio($e);
    expect($transfer->fresh()->status)->toBe(InventoryTransfer::STATUS_RECEIVED)
        ->and(trasladoUbicaciones($e))->toBe(['A1' => 4.0, 'S1' => 3.0])
        ->and((float) InventoryMovement::queryAllBranches()->where('movement_type', 'transfer_loss')->sum('quantity_out'))->toBe(2.0)
        ->and((float) InventoryMovement::queryAllBranches()->where('movement_type', 'transfer_damage')->sum('quantity_out'))->toBe(1.0)
        ->and(InventoryTransferDiscrepancy::whereNull('regularized_at')->count())->toBe(0)
        // La empresa pierde 3 unidades a $5
        ->and((float) $promedio->quantity_on_hand)->toBe(7.0)
        ->and((float) $promedio->inventory_value)->toBe(35.0)
        ->and((float) $promedio->average_unit_cost)->toBe(5.0);

    $this->get(route('inventory-transfers.show', $transfer))->assertOk()->assertSee('Regularizada')
        ->assertSeeInOrder(['Existencias regularizadas', $e->autorizador->username, 'Traslado finalizado: recibido']);
    $this->get(route('inventory.movements', ['type' => 'transfer_damage']))->assertOk()->assertSee('Baja por daño en traslado');
});

test('un sobrante salió de la bodega origen sin registrarse: sale de ahí al regularizar', function () {
    $e = trasladoEscenario();
    $transfer = trasladoDespachado($e, 2);
    $linea = $transfer->details()->sole()->id_inventory_transfer_detail;

    $this->actingAs($e->receptor)->post(route('inventory-transfers.receive', $transfer), [
        'received' => [$linea => ['quantity_received' => 3, 'id_destination_inventory_location' => ubicacionInventario($e->s1)]],
    ])->assertSessionHas('success', 'Recepción registrada con discrepancias: regularícelas para cerrar el traslado.');

    $sobrante = InventoryTransferDiscrepancy::sole();
    expect($transfer->fresh()->status)->toBe(InventoryTransfer::STATUS_WITH_DISCREPANCY)
        ->and($sobrante->discrepancy_type)->toBe(InventoryTransferDiscrepancy::TYPE_SURPLUS)
        ->and((float) $sobrante->quantity)->toBe(1.0)
        // Lo despachado ya salió del vehículo; el sobrante aún no entra
        ->and(trasladoUbicaciones($e))->toBe(['A1' => 8.0, 'S1' => 2.0]);

    // Quien regulariza ve de dónde saldrá
    $this->actingAs($e->autorizador)->get(route('inventory-transfers.show', $transfer))->assertOk()
        ->assertSee("Sale de {$e->bodegaMatriz->name} (hay 8 del lote)");

    $this->post(route('inventory-transfers.regularize', $transfer))->assertSessionHas('success');

    // La caja de más sale de A1: la empresa sigue con 10 a $5
    expect($transfer->fresh()->status)->toBe(InventoryTransfer::STATUS_RECEIVED)
        ->and(trasladoUbicaciones($e))->toBe(['A1' => 7.0, 'S1' => 3.0])
        ->and((float) trasladoPromedio($e)->quantity_on_hand)->toBe(10.0)
        ->and((float) trasladoPromedio($e)->inventory_value)->toBe(50.0)
        ->and(InventoryMovement::queryAllBranches()->where('movement_type', InventoryMovement::TYPE_TRANSFER_SURPLUS_GAIN)->exists())->toBeFalse();

    $this->get(route('inventory.movements', ['type' => InventoryMovement::TYPE_TRANSFER_SURPLUS_OUT]))->assertOk()->assertSee('Sobrante: sale de la bodega origen');
});

test('el sobrante sale primero de la ubicación de la línea y luego de otras del mismo lote', function () {
    $e = trasladoEscenario();
    $a2 = Location::create(['id_warehouse' => $e->bodegaMatriz->id_warehouse, 'code' => 'A2', 'capacity' => 100]);
    (new InventoryService)->cambiarUbicacion($e->lot, $e->bodegaMatriz->id_warehouse, ubicacionInventario($e->a1), ubicacionInventario($a2), 3);

    // A1 queda con 1 y A2 con 3; llegan 9 de 6
    $transfer = trasladoDespachado($e, 6);
    $linea = $transfer->details()->sole()->id_inventory_transfer_detail;
    $this->actingAs($e->receptor)->post(route('inventory-transfers.receive', $transfer), [
        'received' => [$linea => ['quantity_received' => 9]],
    ])->assertSessionHas('success');

    $this->actingAs($e->autorizador)->post(route('inventory-transfers.regularize', $transfer))->assertSessionHas('success');

    $salidas = InventoryMovement::queryAllBranches()->where('movement_type', InventoryMovement::TYPE_TRANSFER_SURPLUS_OUT)
        ->orderBy('id_inventory_movement')->get()
        ->map(fn ($m) => [(int) $m->id_inventory_location, (float) $m->quantity_out])->all();

    expect($salidas)->toBe([[ubicacionInventario($e->a1), 1.0], [ubicacionInventario($a2), 2.0]])
        ->and(trasladoUbicaciones($e))->toBe([$e->lot->id_inventory_lot.'|'.ubicacionInventario($a2) => 1.0, 'S1' => 9.0])
        ->and((float) trasladoPromedio($e)->quantity_on_hand)->toBe(10.0);
});

test('lo que la bodega origen ya no tiene del lote entra como ganancia', function () {
    $e = trasladoEscenario();
    // A1 queda con 1; llegan 12 de 9: 1 sale de A1 y 2 no tienen de dónde salir
    $transfer = trasladoDespachado($e, 9);
    $linea = $transfer->details()->sole()->id_inventory_transfer_detail;
    $this->actingAs($e->receptor)->post(route('inventory-transfers.receive', $transfer), [
        'received' => [$linea => ['quantity_received' => 12]],
    ])->assertSessionHas('success');

    $this->actingAs($e->autorizador)->get(route('inventory-transfers.show', $transfer))->assertOk()
        ->assertSee("Sale 1 de {$e->bodegaMatriz->name};", false)
        ->assertSee('2 entra como ganancia');

    $this->post(route('inventory-transfers.regularize', $transfer))->assertSessionHas('success');

    $porTipo = fn (string $tipo) => (float) InventoryMovement::queryAllBranches()->where('movement_type', $tipo)->sum(DB::raw('quantity_in + quantity_out'));

    expect(trasladoUbicaciones($e))->toBe(['S1' => 12.0])
        ->and($porTipo(InventoryMovement::TYPE_TRANSFER_SURPLUS_OUT))->toBe(1.0)
        ->and($porTipo(InventoryMovement::TYPE_TRANSFER_SURPLUS))->toBe(1.0)
        ->and($porTipo(InventoryMovement::TYPE_TRANSFER_SURPLUS_GAIN))->toBe(2.0)
        // Solo la ganancia sube lo que tiene la empresa: 2 a $5
        ->and((float) trasladoPromedio($e)->quantity_on_hand)->toBe(12.0)
        ->and((float) trasladoPromedio($e)->inventory_value)->toBe(60.0)
        ->and((float) trasladoPromedio($e)->average_unit_cost)->toBe(5.0);

    $this->get(route('inventory.movements', ['type' => InventoryMovement::TYPE_TRANSFER_SURPLUS_GAIN]))->assertOk()->assertSee('Ganancia por sobrante (sin origen)');
});

test('solo se cancela antes de despacharse y solo el borrador se edita o elimina', function () {
    $e = trasladoEscenario();
    $this->actingAs($e->compras);

    // Borrador y autorizado se cancelan
    $this->post(route('inventory-transfers.store'), trasladoDatos($e, 1));
    $borrador = InventoryTransfer::queryAllBranches()->sole();
    $this->post(route('inventory-transfers.cancel', $borrador))->assertSessionHas('success');
    expect($borrador->fresh()->status)->toBe(InventoryTransfer::STATUS_CANCELLED);

    $this->post(route('inventory-transfers.store'), trasladoDatos($e, 1));
    $autorizado = InventoryTransfer::queryAllBranches()->latest('id_inventory_transfer')->first();
    $this->post(route('inventory-transfers.submit', $autorizado));
    $this->actingAs($e->autorizador)->post(route('inventory-transfers.authorize', $autorizado));
    $this->actingAs($e->compras)->delete(route('inventory-transfers.destroy', $autorizado))->assertSessionHas('error');
    $this->post(route('inventory-transfers.cancel', $autorizado))->assertSessionHas('success');

    // En tránsito ya no: se recibe
    $transfer = trasladoDespachado($e, 3);
    $this->post(route('inventory-transfers.cancel', $transfer))->assertSessionHas('error', 'Solo se cancela un traslado que no se ha despachado: uno en tránsito se recibe.');
    $this->post(route('inventory-transfers.dispatch', $transfer), ['id_vehicle_location' => $e->cajon->id_vehicle_location])
        ->assertSessionHas('error', 'Solo se despacha un traslado en preparación.');
    $this->get(route('inventory-transfers.edit', $transfer))->assertRedirect(route('inventory-transfers.show', $transfer));
    $this->get(route('inventory-transfers.show', $transfer))->assertOk()->assertDontSee(route('inventory-transfers.cancel', $transfer));

    expect($transfer->fresh()->status)->toBe(InventoryTransfer::STATUS_IN_TRANSIT)
        ->and(VehicleLocation::whereKey($e->cajon->id_vehicle_location)->exists())->toBeTrue();
});

test('cada paso del traslado requiere su permiso', function () {
    $e = trasladoEscenario();

    $this->actingAs($e->receptor)->get(route('inventory-transfers.create'))->assertForbidden();
    $this->post(route('inventory-transfers.store'), trasladoDatos($e, 1))->assertForbidden();

    $transfer = trasladoDespachado($e, 1);
    $this->actingAs($e->compras)->post(route('inventory-transfers.receive', $transfer))->assertForbidden();
    $this->get(route('inventory-transfers.discrepancies', $transfer))->assertForbidden();
    $this->post(route('inventory-transfers.regularize', $transfer))->assertForbidden();
    $this->actingAs($e->receptor)->post(route('inventory-transfers.prepare', $transfer))->assertForbidden();
    $this->post(route('inventory-transfers.reject', $transfer), ['status_reason' => 'x'])->assertForbidden();

    // Las pantallas del módulo cargan para quien tiene el permiso
    $this->actingAs($e->compras);
    $this->get(route('inventory-transfers.index', ['status' => 'in_transit']))->assertOk()->assertSee($transfer->transfer_code)->assertSee('En tránsito');
    $this->get(route('inventory-transfers.create', [
        'id_source_warehouse' => $e->bodegaMatriz->id_warehouse, 'id_destination_warehouse' => $e->bodegaSucursal->id_warehouse,
    ]))->assertOk()->assertSee($e->lot->lot_code)->assertSee($e->camion->license_plate);
});

test('cada impresión de etiquetas queda registrada y la reimpresión exige el motivo', function () {
    $e = trasladoEscenario();
    $this->actingAs($e->compras);

    $this->get(route('inventory.labels', $e->lot))->assertOk()->assertSee('Imprimir etiquetas');

    $hoja = $this->post(route('inventory.labels.store', $e->lot), ['quantity_printed' => 3]);
    $primera = InventoryLabelPrint::sole();
    $hoja->assertRedirect(route('inventory.labels.print', $primera));
    // Cada viñeta lleva el código de barras del SKU (código interno), el lote y la fecha de recepción
    $hojaImpresa = $this->get(route('inventory.labels.print', $primera))->assertOk()
        ->assertSee($e->product->sku)->assertSee($e->lot->lot_code)->assertSee($e->lot->received_at->format('d/m/Y'))
        ->getContent();
    expect(substr_count($hojaImpresa, '/3</p>'))->toBe(3)
        ->and(substr_count($hojaImpresa, '<svg'))->toBe(3)
        ->and($primera->reprint_reason)->toBeNull();

    $this->post(route('inventory.labels.store', $e->lot), ['quantity_printed' => 2])->assertSessionHasErrors('reprint_reason');
    $this->post(route('inventory.labels.store', $e->lot), ['quantity_printed' => 2, 'reprint_reason' => 'Etiquetas dañadas'])->assertSessionHasNoErrors();

    expect(InventoryLabelPrint::count())->toBe(2)
        ->and(InventoryLabelPrint::latest('id_inventory_label_print')->first()->reprint_reason)->toBe('Etiquetas dañadas');

    // Sin el permiso no se imprimen (aunque se vea el lote); la sucursal ni siquiera lo ve
    $this->actingAs(trasladoUsuario($e->matriz, ['inventory.ver']))->get(route('inventory.labels', $e->lot))->assertForbidden();
    $this->actingAs($e->receptor)->get(route('inventory.labels', $e->lot))->assertNotFound();
});
