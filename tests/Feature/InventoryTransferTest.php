<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\InventoryCostAverage;
use App\Models\InventoryLabelPrint;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\InventoryTransfer;
use App\Models\Location;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// =============================================================================
// Traslados: Casa Matriz despacha lo que entró con las compras y la sucursal lo recibe;
// lo que no llega es merma. Y etiquetas de lote, con motivo en cada reimpresión.
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

/** Casa Matriz con un lote de 10 a $5 en A1, como lo deja una compra recibida */
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
        'id_location' => $a1->id_location, 'movement_type' => InventoryMovement::TYPE_PURCHASE_RECEIPT, 'movement_date' => now(),
        'quantity_in' => 10, 'quantity_out' => 0, 'unit_cost' => 5, 'total_cost' => 50,
    ]);
    InventoryCostAverage::create(['id_company' => $company->id_company, 'id_product' => $product->id_product, 'quantity_on_hand' => 10, 'inventory_value' => 50, 'average_unit_cost' => 5]);

    return (object) [
        'company' => $company, 'matriz' => $matriz, 'sucursal' => $sucursal,
        'bodegaMatriz' => $bodegaMatriz, 'bodegaSucursal' => $bodegaSucursal, 'a1' => $a1, 's1' => $s1,
        'product' => $product, 'lot' => $lot,
        'compras' => trasladoUsuario($matriz, ['inventory.ver', 'inventory.trasladar', 'inventory.etiquetas']),
        'receptor' => trasladoUsuario($sucursal, ['inventory.ver', 'inventory.recibir_traslados']),
    ];
}

function trasladoDatos(object $e, float $cantidad): array
{
    return [
        'id_source_warehouse' => $e->bodegaMatriz->id_warehouse,
        'id_destination_warehouse' => $e->bodegaSucursal->id_warehouse,
        'transfer_date' => now()->toDateString(),
        'details' => [[
            'id_inventory_lot' => $e->lot->id_inventory_lot, 'id_source_location' => $e->a1->id_location,
            'quantity_dispatched' => $cantidad, 'id_destination_location' => $e->s1->id_location,
        ]],
    ];
}

function trasladoExistencia(Warehouse $warehouse, InventoryLot $lot): float
{
    return (float) InventoryMovement::queryAllBranches()
        ->where('id_warehouse', $warehouse->id_warehouse)
        ->where('id_inventory_lot', $lot->id_inventory_lot)
        ->sum(DB::raw('quantity_in - quantity_out'));
}

function trasladoPromedio(object $e): InventoryCostAverage
{
    return InventoryCostAverage::queryAllBranches()->where('id_company', $e->company->id_company)->where('id_product', $e->product->id_product)->sole();
}

test('Casa Matriz despacha y la sucursal recibe: la existencia pasa de una bodega a la otra', function () {
    $e = trasladoEscenario();

    $this->actingAs($e->compras)->post(route('inventory-transfers.store'), trasladoDatos($e, 6))->assertSessionHasNoErrors();
    $transfer = InventoryTransfer::queryAllBranches()->sole();

    // El borrador no mueve nada
    expect($transfer->status)->toBe('draft')
        ->and($transfer->transfer_code)->toStartWith('TRF-')
        ->and(trasladoExistencia($e->bodegaMatriz, $e->lot))->toBe(10.0);

    $this->post(route('inventory-transfers.dispatch', $transfer))->assertSessionHas('success');
    expect($transfer->fresh()->status)->toBe('dispatched')
        ->and(trasladoExistencia($e->bodegaMatriz, $e->lot))->toBe(4.0)
        ->and(trasladoExistencia($e->bodegaSucursal, $e->lot))->toBe(0.0);

    // La sucursal ve el traslado y lo recibe en S1
    $this->actingAs($e->receptor)->get(route('inventory-transfers.show', $transfer))->assertOk()->assertSee('Confirmar recepción');
    $detalle = $transfer->details()->sole();
    $this->post(route('inventory-transfers.receive', $transfer), [
        'received' => [$detalle->id_inventory_transfer_detail => ['quantity_received' => 6, 'id_destination_location' => $e->s1->id_location]],
    ])->assertSessionHas('success');

    $entrada = InventoryMovement::queryAllBranches()->where('movement_type', 'transfer_in')->sole();
    $promedio = trasladoPromedio($e);

    expect($transfer->fresh()->status)->toBe('received')
        ->and(trasladoExistencia($e->bodegaSucursal, $e->lot))->toBe(6.0)
        ->and($entrada->id_location)->toBe($e->s1->id_location)
        ->and((float) $entrada->unit_cost)->toBe(5.0)
        // Sigue en la empresa: el promedio no cambia
        ->and((float) $promedio->quantity_on_hand)->toBe(10.0)
        ->and((float) $promedio->inventory_value)->toBe(50.0);

    $this->actingAs($e->receptor)->get(route('inventory.index'))->assertOk()->assertSee($e->lot->lot_code);
});

test('no se traslada más de lo que hay del lote en su ubicación', function () {
    $e = trasladoEscenario();
    $this->actingAs($e->compras);

    $this->post(route('inventory-transfers.store'), trasladoDatos($e, 12))->assertSessionHasErrors('details.0.quantity_dispatched');

    // Dos borradores de 6 caben por separado, pero no se pueden despachar los dos
    $this->post(route('inventory-transfers.store'), trasladoDatos($e, 6))->assertSessionHasNoErrors();
    $this->post(route('inventory-transfers.store'), trasladoDatos($e, 6))->assertSessionHasNoErrors();
    [$primero, $segundo] = InventoryTransfer::queryAllBranches()->orderBy('id_inventory_transfer')->get();

    $this->post(route('inventory-transfers.dispatch', $primero))->assertSessionHas('success');
    $this->post(route('inventory-transfers.dispatch', $segundo))
        ->assertSessionHas('error', "No se puede despachar: Del lote {$e->lot->lot_code} solo hay 4 en esa ubicación de la bodega origen.");

    expect($segundo->fresh()->status)->toBe('draft')
        ->and(trasladoExistencia($e->bodegaMatriz, $e->lot))->toBe(4.0);
});

test('lo que no llega se registra como merma y sale del inventario de la empresa', function () {
    $e = trasladoEscenario();
    $this->actingAs($e->compras)->post(route('inventory-transfers.store'), trasladoDatos($e, 6));
    $transfer = InventoryTransfer::queryAllBranches()->sole();
    $this->post(route('inventory-transfers.dispatch', $transfer));

    $detalle = $transfer->details()->sole();
    $this->actingAs($e->receptor)->post(route('inventory-transfers.receive', $transfer), [
        'received' => [$detalle->id_inventory_transfer_detail => ['quantity_received' => 5, 'id_destination_location' => $e->s1->id_location]],
    ])->assertSessionHas('success');

    $merma = InventoryMovement::queryAllBranches()->where('movement_type', 'transfer_loss')->sole();
    $promedio = trasladoPromedio($e);

    expect(trasladoExistencia($e->bodegaSucursal, $e->lot))->toBe(5.0)
        ->and((float) $merma->quantity_out)->toBe(1.0)
        ->and((float) $merma->total_cost)->toBe(5.0)
        ->and((float) $detalle->fresh()->quantity_received)->toBe(5.0)
        ->and((float) $promedio->quantity_on_hand)->toBe(9.0)
        ->and((float) $promedio->inventory_value)->toBe(45.0)
        ->and((float) $promedio->average_unit_cost)->toBe(5.0);

    // No se recibe más de lo despachado
    $this->actingAs($e->compras)->post(route('inventory-transfers.store'), trasladoDatos($e, 2));
    $otro = InventoryTransfer::queryAllBranches()->latest('id_inventory_transfer')->first();
    $this->post(route('inventory-transfers.dispatch', $otro));
    $this->actingAs($e->receptor)->post(route('inventory-transfers.receive', $otro), [
        'received' => [$otro->details()->sole()->id_inventory_transfer_detail => ['quantity_received' => 3]],
    ])->assertSessionHasErrors();
    expect($otro->fresh()->status)->toBe('dispatched');
});

test('solo los borradores se editan, cancelan o eliminan, y solo un despachado se recibe', function () {
    $e = trasladoEscenario();
    $this->actingAs($e->compras)->post(route('inventory-transfers.store'), trasladoDatos($e, 3));
    $transfer = InventoryTransfer::queryAllBranches()->sole();

    // Un borrador no se recibe
    $this->actingAs($e->receptor)->post(route('inventory-transfers.receive', $transfer))
        ->assertSessionHas('error', 'Solo se puede recibir un traslado despachado.');

    $this->actingAs($e->compras)->post(route('inventory-transfers.dispatch', $transfer))->assertSessionHas('success');
    $this->post(route('inventory-transfers.dispatch', $transfer))->assertSessionHas('error', 'Solo se puede despachar un traslado en borrador.');
    $this->post(route('inventory-transfers.cancel', $transfer))->assertSessionHas('error');
    $this->delete(route('inventory-transfers.destroy', $transfer))->assertSessionHas('error');
    $this->get(route('inventory-transfers.edit', $transfer))->assertRedirect(route('inventory-transfers.show', $transfer));

    expect($transfer->fresh()->status)->toBe('dispatched');

    // Un borrador sí se cancela
    $this->post(route('inventory-transfers.store'), trasladoDatos($e, 1));
    $borrador = InventoryTransfer::queryAllBranches()->latest('id_inventory_transfer')->first();
    $this->post(route('inventory-transfers.cancel', $borrador))->assertSessionHas('success');
    expect($borrador->fresh()->status)->toBe('cancelled');
});

test('crear y despachar traslados, y recibirlos, requieren su permiso', function () {
    $e = trasladoEscenario();

    $this->actingAs($e->receptor)->get(route('inventory-transfers.create'))->assertForbidden();
    $this->post(route('inventory-transfers.store'), trasladoDatos($e, 1))->assertForbidden();

    $this->actingAs($e->compras)->post(route('inventory-transfers.store'), trasladoDatos($e, 1));
    $transfer = InventoryTransfer::queryAllBranches()->sole();
    $this->post(route('inventory-transfers.dispatch', $transfer));
    $this->post(route('inventory-transfers.receive', $transfer))->assertForbidden();

    // Las pantallas del módulo cargan para quien tiene el permiso
    $this->get(route('inventory-transfers.index'))->assertOk()->assertSee($transfer->transfer_code);
    $this->get(route('inventory-transfers.create', [
        'id_source_warehouse' => $e->bodegaMatriz->id_warehouse, 'id_destination_warehouse' => $e->bodegaSucursal->id_warehouse,
    ]))->assertOk()->assertSee($e->lot->lot_code);
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
