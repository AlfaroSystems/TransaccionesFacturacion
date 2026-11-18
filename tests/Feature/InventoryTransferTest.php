<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\InventoryAdjustment;
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
use Illuminate\Validation\ValidationException;

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

test('lo autorizado queda apartado: otro traslado ni un cambio de ubicación pueden usarlo', function () {
    $e = trasladoEscenario();
    $this->actingAs($e->compras);

    // Los borradores y lo que está por autorizar no apartan: los dos se envían
    foreach ([1, 2] as $vez) {
        $this->post(route('inventory-transfers.store'), trasladoDatos($e, 6))->assertSessionHasNoErrors();
    }
    [$primero, $segundo] = InventoryTransfer::queryAllBranches()->orderBy('id_inventory_transfer')->get();

    foreach ([$primero, $segundo] as $transfer) {
        $this->post(route('inventory-transfers.submit', $transfer))->assertSessionHas('success');
    }

    // Autorizado el primero, sus 6 quedan apartados: el segundo ya no se autoriza
    $this->actingAs($e->autorizador)->post(route('inventory-transfers.authorize', $primero))->assertSessionHas('success');
    $this->post(route('inventory-transfers.authorize', $segundo))->assertSessionHas('error',
        "No hay existencia suficiente: Del lote {$e->lot->lot_code} solo hay 4 disponibles en esa ubicación de la bodega origen (6 apartadas para otros traslados autorizados).");
    expect($segundo->fresh()->status)->toBe(InventoryTransfer::STATUS_PENDING_AUTHORIZATION);

    // Un borrador nuevo tampoco pide más de lo disponible, y el formulario lo muestra
    $this->actingAs($e->compras)->post(route('inventory-transfers.store'), trasladoDatos($e, 5))->assertSessionHasErrors('details.0.quantity_requested');
    $this->get(route('inventory-transfers.create', ['id_source_warehouse' => $e->bodegaMatriz->id_warehouse, 'id_destination_warehouse' => $e->bodegaSucursal->id_warehouse]))
        ->assertOk()->assertSee('6 apartadas para otros traslados');

    // Lo apartado no se cambia de ubicación; lo libre sí
    $a2 = Location::create(['id_warehouse' => $e->bodegaMatriz->id_warehouse, 'code' => 'A2', 'capacity' => 100]);
    $mover = fn (float $cantidad) => (new InventoryService)->cambiarUbicacion($e->lot, $e->bodegaMatriz->id_warehouse, ubicacionInventario($e->a1), ubicacionInventario($a2), $cantidad);
    expect(fn () => $mover(5))->toThrow(ValidationException::class, 'están apartadas para traslados autorizados: solo se pueden mover 4.');
    $mover(4);
    $this->get(route('inventory.lots.show', $e->lot))->assertOk()->assertSee('6 apartadas para traslados');

    // Al cancelar el primero se libera, y el segundo se autoriza y se despacha
    $this->post(route('inventory-transfers.cancel', $primero))->assertSessionHas('success');
    $this->actingAs($e->autorizador)->post(route('inventory-transfers.authorize', $segundo))->assertSessionHas('success');
    $this->actingAs($e->compras)->post(route('inventory-transfers.prepare', $segundo))->assertSessionHas('success');
    $this->post(route('inventory-transfers.dispatch', $segundo), ['id_vehicle_location' => $e->cajon->id_vehicle_location])->assertSessionHas('success');

    expect(trasladoUbicaciones($e))->toBe([$e->lot->id_inventory_lot.'|'.ubicacionInventario($a2) => 4.0, 'CAJON-1' => 6.0])
        ->and(InventoryTransfer::apartado())->toBeEmpty();
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

test('lo que llegó y no venía en el traslado se agrega como línea y sale de la bodega origen', function () {
    $e = trasladoEscenario();
    // Otro lote en A1: 4 mouses a $3
    $mouse = Product::create(['name' => 'Mouse '.$e->lot->lot_code, 'sku' => 'M-'.$e->lot->lot_code]);
    $otro = InventoryLot::create([
        'lot_code' => 'OTRO-'.$e->lot->lot_code, 'id_product' => $mouse->id_product, 'id_warehouse' => $e->bodegaMatriz->id_warehouse, 'received_at' => now(),
        'quantity_received' => 4, 'unit_cost' => 3, 'total_cost' => 12, 'status' => InventoryLot::STATUS_ACTIVE, 'placed_at' => now(),
    ]);
    InventoryMovement::create([
        'id_product' => $mouse->id_product, 'id_inventory_lot' => $otro->id_inventory_lot, 'id_warehouse' => $e->bodegaMatriz->id_warehouse,
        'id_inventory_location' => ubicacionInventario($e->a1), 'movement_type' => InventoryMovement::TYPE_PURCHASE_RECEIPT, 'movement_date' => now(),
        'quantity_in' => 4, 'quantity_out' => 0, 'unit_cost' => 3, 'total_cost' => 12,
    ]);
    InventoryCostAverage::create(['id_company' => $e->company->id_company, 'id_product' => $mouse->id_product, 'quantity_on_hand' => 4, 'inventory_value' => 12, 'average_unit_cost' => 3]);

    // Llegan 5 de las 6 laptops, y además 2 mouses que nadie pidió
    $transfer = trasladoDespachado($e, 6);
    $linea = $transfer->details()->sole()->id_inventory_transfer_detail;
    $s1 = ubicacionInventario($e->s1);
    $this->actingAs($e->receptor)->post(route('inventory-transfers.receive', $transfer), ['received' => [$linea => ['quantity_received' => 5]]])->assertSessionHas('success');

    $this->get(route('inventory-transfers.discrepancies', $transfer))->assertOk()
        ->assertSee('Llegó algo que no venía en el traslado')->assertSee($otro->lot_code);

    $registrar = fn (array $extras, array $lineas = []) => $this->post(route('inventory-transfers.discrepancies.store', $transfer), [
        'discrepancies' => $lineas + [$linea => ['shortage' => 1]],
        'extras' => $extras,
    ]);

    $registrar([['id_inventory_lot' => $e->lot->id_inventory_lot, 'quantity' => 1, 'id_destination_inventory_location' => $s1]])
        ->assertSessionHasErrors(['extras.0.id_inventory_lot' => 'Ese lote ya viene en el traslado: registre lo que llegó de más como sobrante en su línea.']);
    $registrar([['id_inventory_lot' => $otro->id_inventory_lot, 'quantity' => 2]])->assertSessionHasErrors('extras.0.id_destination_inventory_location');

    $mouses = [['id_inventory_lot' => $otro->id_inventory_lot, 'quantity' => 2, 'id_destination_inventory_location' => $s1, 'notes' => 'Venían en la misma tarima']];
    $registrar($mouses)->assertSessionHasNoErrors();
    $nueva = $transfer->details()->where('id_inventory_lot', $otro->id_inventory_lot)->sole();
    expect($nueva->isUnexpected())->toBeTrue()
        ->and((float) $nueva->discrepancies()->sole()->quantity)->toBe(2.0);
    $this->get(route('inventory-transfers.show', $transfer))->assertOk()->assertSee('No venía en el traslado');

    // Corregida sin ese sobrante, la línea agregada se quita
    $registrar([], [$nueva->id_inventory_transfer_detail => ['surplus' => 0]])->assertSessionHasNoErrors();
    expect($transfer->details()->count())->toBe(1);

    $registrar($mouses)->assertSessionHasNoErrors();
    $this->actingAs($e->autorizador)->post(route('inventory-transfers.regularize', $transfer))->assertSessionHas('success');

    // Los 2 mouses salen de A1 y entran a S1: la empresa sigue con 4 a $3; la laptop faltante se pierde
    $ubicaciones = trasladoUbicaciones($e);
    $promedioMouse = InventoryCostAverage::queryAllBranches()->where('id_company', $e->company->id_company)->where('id_product', $mouse->id_product)->sole();
    expect($transfer->fresh()->status)->toBe(InventoryTransfer::STATUS_RECEIVED)
        ->and($ubicaciones)->toHaveCount(4)
        ->and($ubicaciones['A1'])->toBe(4.0)
        ->and($ubicaciones['S1'])->toBe(5.0)
        ->and($ubicaciones[$otro->id_inventory_lot.'|'.ubicacionInventario($e->a1)])->toBe(2.0)
        ->and($ubicaciones[$otro->id_inventory_lot.'|'.$s1])->toBe(2.0)
        ->and((float) $promedioMouse->quantity_on_hand)->toBe(4.0)
        ->and((float) $promedioMouse->inventory_value)->toBe(12.0)
        ->and((float) trasladoPromedio($e)->quantity_on_hand)->toBe(9.0);
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

// =============================================================================
// Ajustes de inventario por conteo físico
// =============================================================================

test('un ajuste por conteo deja lo contado y mueve la existencia y el valor de la empresa', function () {
    $e = trasladoEscenario();
    $ajustador = trasladoUsuario($e->matriz, ['inventory.ver', 'inventory.ajustar']);
    $a1 = ubicacionInventario($e->a1);
    $ajustar = fn (array $datos) => $this->post(route('inventory.lots.adjust', $e->lot), $datos + ['id_inventory_location' => $a1, 'reason' => 'count']);

    // Sin el permiso no se ajusta
    $this->actingAs($e->compras);
    $ajustar(['quantity_counted' => 8])->assertForbidden();

    $this->actingAs($ajustador);
    $this->get(route('inventory.lots.show', $e->lot))->assertOk()->assertSee('Ajustes de inventario')->assertSee('Registrar ajuste');

    $ajustar(['quantity_counted' => 10])->assertSessionHasErrors(['quantity_counted' => 'Lo contado es igual a lo que dice el sistema (10): no hay nada que ajustar.']);
    $ajustar(['quantity_counted' => 8, 'reason' => 'other'])->assertSessionHasErrors('notes');
    // Solo bodegas donde está o entró el lote
    $ajustar(['quantity_counted' => 1, 'id_inventory_location' => ubicacionInventario($e->s1)])->assertSessionHasErrors('id_inventory_location');

    // Faltan 2: salen a $5
    $ajustar(['quantity_counted' => 8, 'reason' => 'loss', 'notes' => 'No aparecieron'])
        ->assertSessionHas('success', fn ($mensaje) => str_contains($mensaje, 'registrado: salieron 2 al costo del lote ($10.00).'));

    $ajuste = InventoryAdjustment::sole();
    expect(trasladoUbicaciones($e))->toBe(['A1' => 8.0])
        ->and((float) trasladoPromedio($e)->quantity_on_hand)->toBe(8.0)
        ->and((float) trasladoPromedio($e)->inventory_value)->toBe(40.0)
        ->and($ajuste->adjustment_code)->toStartWith('AJU-'.now()->year.'-')
        ->and((float) $ajuste->quantity_before)->toBe(10.0)
        ->and((float) $ajuste->difference)->toBe(-2.0)
        ->and($ajuste->reason)->toBe(InventoryAdjustment::REASON_LOSS);

    // Aparecen 3 más: entran a $5
    $ajustar(['quantity_counted' => 11])->assertSessionHas('success', fn ($mensaje) => str_contains($mensaje, 'entraron 3'));
    expect(trasladoUbicaciones($e))->toBe(['A1' => 11.0])
        ->and((float) trasladoPromedio($e)->quantity_on_hand)->toBe(11.0)
        ->and((float) trasladoPromedio($e)->inventory_value)->toBe(55.0);

    $this->get(route('inventory.movements', ['type' => InventoryMovement::TYPE_ADJUSTMENT_OUT]))->assertOk()
        ->assertSee('Ajuste de inventario (sale)')->assertSee($ajuste->adjustment_code);
    $this->get(route('inventory.lots.show', $e->lot))->assertOk()->assertSee('Pérdida o robo')->assertSee('No aparecieron');
});

test('el ajuste avisa cuando lo contado no alcanza para lo apartado en un traslado', function () {
    $e = trasladoEscenario();
    $ajustador = trasladoUsuario($e->matriz, ['inventory.ver', 'inventory.ajustar']);

    // 6 quedan apartados para un traslado autorizado
    $this->actingAs($e->compras)->post(route('inventory-transfers.store'), trasladoDatos($e, 6));
    $transfer = InventoryTransfer::queryAllBranches()->sole();
    $this->post(route('inventory-transfers.submit', $transfer));
    $this->actingAs($e->autorizador)->post(route('inventory-transfers.authorize', $transfer))->assertSessionHas('success');

    // Se cuentan 4: el ajuste se registra (es lo que hay) y avisa
    $this->actingAs($ajustador)->post(route('inventory.lots.adjust', $e->lot), [
        'id_inventory_location' => ubicacionInventario($e->a1), 'quantity_counted' => 4, 'reason' => 'damage',
    ])->assertSessionHas('success')->assertSessionHas('warning', 'Hay 4 en esa ubicación, pero 6 están apartadas para traslados autorizados: no alcanzará al despacharlos.');

    expect(trasladoUbicaciones($e))->toBe(['A1' => 4.0]);
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
