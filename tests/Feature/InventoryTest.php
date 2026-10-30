<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\InventoryCostAverage;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\Location;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\Retaceo;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseCategory;
use App\Services\RetaceoService;
use Illuminate\Support\Str;

// =============================================================================
// Inventario (fase 1): lo recibido en compras entra como lotes en su bodega y ubicación,
// con su costo; el retaceo corrige el costo, anular la compra lo saca, y cada empresa
// lleva el costo promedio de sus productos.
// =============================================================================

function inventarioEscenario(): object
{
    $tag = Str::upper(Str::random(5));
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::firstOrCreate(['name' => 'admin'])->id_role, ['assigned_at' => now()]);

    $company = Company::create(['name' => "Empresa {$tag}"]);
    $matriz = Branch::create(['id_company' => $company->id_company, 'name' => "Casa Matriz {$tag}", 'is_purchasing_department' => true]);
    $sucursal = Branch::create(['id_company' => $company->id_company, 'name' => "Sucursal {$tag}"]);
    $category = WarehouseCategory::create(['name' => "Categoría {$tag}"]);
    $bodega = fn (Branch $branch, string $name) => Warehouse::create(['id_branch' => $branch->id_branch, 'id_warehouse_category' => $category->id_warehouse_category, 'name' => "{$name} {$tag}"]);
    $ubicacion = fn (Warehouse $warehouse, string $code) => Location::create(['id_warehouse' => $warehouse->id_warehouse, 'code' => $code, 'capacity' => 100]);

    $bodegaMatriz = $bodega($matriz, 'Bodega Central');
    $bodegaSucursal = $bodega($sucursal, 'Bodega Sucursal');
    $unit = Unit::create(['name' => "Unidad {$tag}", 'abbreviation' => 'UND']);
    $supplier = Supplier::create(['name' => "Proveedor {$tag}", 'email' => Str::lower($tag).'@example.com', 'country' => 'El Salvador', 'is_active' => true]);

    return (object) [
        'admin' => $admin, 'company' => $company, 'matriz' => $matriz, 'sucursal' => $sucursal,
        'bodegaMatriz' => $bodegaMatriz, 'bodegaSucursal' => $bodegaSucursal,
        'a1' => $ubicacion($bodegaMatriz, 'A1'), 'a2' => $ubicacion($bodegaMatriz, 'A2'), 's1' => $ubicacion($bodegaSucursal, 'S1'),
        'unit' => $unit, 'supplier' => $supplier,
        'laptop' => Product::create(['name' => "Laptop {$tag}", 'sku' => 'L-'.$tag]),
        'monitor' => Product::create(['name' => "Monitor {$tag}", 'sku' => 'M-'.$tag]),
    ];
}

/** Orden emitida para la bodega indicada; $lineas: [[producto, cantidad, precio], ...] */
function inventarioOrden(object $e, Warehouse $warehouse, array $lineas): object
{
    $order = PurchaseOrder::create([
        'id_supplier' => $e->supplier->id_supplier, 'id_branch' => $warehouse->id_branch, 'id_warehouse' => $warehouse->id_warehouse,
        'id_user' => $e->admin->id_user, 'order_date' => now(), 'expected_date' => now()->addWeek(),
        'currency' => 'USD', 'payment_terms' => 'Contado', 'status' => 'issued',
    ]);

    $detalles = array_map(fn ($l) => PurchaseOrderDetail::create([
        'id_purchase_order' => $order->id_purchase_order, 'id_product' => $l[0]->id_product,
        'quantity' => $l[1], 'id_unit' => $e->unit->id_unit, 'unit_price' => $l[2],
    ]), $lineas);

    return (object) ['order' => $order, 'lineas' => $detalles];
}

/** Datos de la compra; $lineas: [[línea de orden, cantidad, precio, ubicación|null], ...] */
function inventarioCompra(object $e, object $orden, array $lineas, string $status = 'received'): array
{
    return [
        'id_purchase_order' => $orden->order->id_purchase_order,
        'id_supplier' => $e->supplier->id_supplier,
        'purchase_date' => now()->toDateString(),
        'status' => $status,
        'details' => array_map(fn ($l) => [
            'id_purchase_order_detail' => $l[0]->id_purchase_order_detail,
            'id_product' => $l[0]->id_product,
            'quantity_received' => $l[1],
            'unit_price' => $l[2],
            'id_location' => $l[3]?->id_location,
        ], $lineas),
    ];
}

function inventarioPromedio(object $e, Product $product): ?InventoryCostAverage
{
    return InventoryCostAverage::queryAllBranches()
        ->where('id_company', $e->company->id_company)
        ->where('id_product', $product->id_product)
        ->first();
}

test('lo recibido entra como un lote por línea en su ubicación y la empresa promedia su costo', function () {
    $e = inventarioEscenario();
    $this->actingAs($e->admin);
    $orden = inventarioOrden($e, $e->bodegaMatriz, [[$e->laptop, 10, 10], [$e->monitor, 5, 20]]);
    [$lineaLaptop, $lineaMonitor] = $orden->lineas;

    // La ubicación debe ser de la bodega de la compra
    $this->post(route('purchases.store'), inventarioCompra($e, $orden, [[$lineaLaptop, 6, 10, $e->s1]]))
        ->assertSessionHasErrors('details.0.id_location');

    // 6 laptops a $10 en A1 y los 5 monitores sin ubicación
    $this->post(route('purchases.store'), inventarioCompra($e, $orden, [[$lineaLaptop, 6, 10, $e->a1], [$lineaMonitor, 5, 20, null]]))
        ->assertSessionHasNoErrors();

    $lotes = InventoryLot::queryAllBranches()->orderBy('id_inventory_lot')->get();
    expect($lotes)->toHaveCount(2)
        ->and($lotes[0]->lot_code)->toStartWith('LOT-'.now()->year.'-')
        ->and((float) $lotes[0]->quantity_received)->toBe(6.0)
        ->and((float) $lotes[0]->unit_cost)->toBe(10.0)
        ->and((float) $lotes[1]->total_cost)->toBe(100.0);

    $entradas = InventoryMovement::queryAllBranches()->orderBy('id_inventory_movement')->get();
    expect($entradas->pluck('movement_type')->unique()->all())->toBe(['purchase_receipt'])
        ->and($entradas[0]->id_warehouse)->toBe($e->bodegaMatriz->id_warehouse)
        ->and($entradas[0]->id_location)->toBe($e->a1->id_location)
        ->and($entradas[1]->id_location)->toBeNull();

    // Las otras 4 laptops llegan a $13: el promedio es (60 + 52) / 10
    $this->post(route('purchases.store'), inventarioCompra($e, $orden, [[$lineaLaptop, 4, 13, $e->a2]]))->assertSessionHasNoErrors();

    $promedio = inventarioPromedio($e, $e->laptop);
    expect((float) $promedio->quantity_on_hand)->toBe(10.0)
        ->and((float) $promedio->inventory_value)->toBe(112.0)
        ->and((float) $promedio->average_unit_cost)->toBe(11.2);

    // Existencias: un renglón por lote y ubicación; por producto, con el promedio
    $filas = $this->get(route('inventory.index'))->assertOk()->assertSee($lotes[0]->lot_code)->viewData('stock');
    expect($filas->total())->toBe(3);

    $this->get(route('inventory.index', ['id_warehouse' => $e->bodegaMatriz->id_warehouse, 'search' => $e->laptop->sku]))
        ->assertOk()->assertSee('A1')->assertSee('A2')->assertDontSee($e->monitor->name);

    $this->get(route('inventory.index', ['vista' => 'productos']))->assertOk()->assertSee('11.2000')->assertSee('$112.00');

    $compra = Purchase::queryAllBranches()->orderBy('id_purchase')->first();
    $this->get(route('inventory.movements'))->assertOk()->assertSee('Entrada por compra')->assertSee($compra->purchase_code);
    $this->get(route('purchases.show', $compra))->assertOk()->assertSee($lotes[0]->lot_code)->assertSee('A1');
});

test('un borrador no entra; al recibirlo sí, y completarlo no lo duplica', function () {
    $e = inventarioEscenario();
    $this->actingAs($e->admin);
    $orden = inventarioOrden($e, $e->bodegaMatriz, [[$e->laptop, 10, 10]]);

    $this->post(route('purchases.store'), inventarioCompra($e, $orden, [[$orden->lineas[0], 10, 10, $e->a1]], 'draft'))->assertSessionHasNoErrors();
    $compra = Purchase::queryAllBranches()->sole();
    expect(InventoryLot::queryAllBranches()->count())->toBe(0);

    $this->patch(route('purchases.updateStatus', $compra), ['status' => 'received'])->assertSessionHas('success');
    $this->patch(route('purchases.updateStatus', $compra), ['status' => 'completed'])->assertSessionHas('success');

    expect(InventoryLot::queryAllBranches()->count())->toBe(1)
        ->and(InventoryMovement::queryAllBranches()->count())->toBe(1)
        ->and(InventoryMovement::queryAllBranches()->value('id_location'))->toBe($e->a1->id_location)
        ->and((float) inventarioPromedio($e, $e->laptop)->quantity_on_hand)->toBe(10.0);
});

test('al aplicar el retaceo el lote toma su costo y la diferencia va al valor del inventario', function () {
    $e = inventarioEscenario();
    $this->actingAs($e->admin);
    $orden = inventarioOrden($e, $e->bodegaMatriz, [[$e->laptop, 10, 10]]);

    $this->post(route('purchases.store'), inventarioCompra($e, $orden, [[$orden->lineas[0], 10, 10, $e->a1]]))->assertSessionHasNoErrors();
    $compra = Purchase::queryAllBranches()->with('details')->sole();

    $retaceo = Retaceo::queryAllBranches()->create([
        'id_supplier' => $e->supplier->id_supplier, 'id_purchase' => $compra->id_purchase,
        'retaceo_date' => now(), 'total_cost' => 120, 'status' => 'calculated',
    ]);
    $retaceo->details()->create([
        'id_purchase_detail' => $compra->details[0]->id_purchase_detail, 'id_product' => $e->laptop->id_product,
        'quantity' => 10, 'unit_cost' => 12, 'total_cost' => 120,
    ]);

    app(RetaceoService::class)->cambiarEstado($retaceo, 'applied');

    $lote = InventoryLot::queryAllBranches()->sole();
    $ajuste = InventoryMovement::queryAllBranches()->where('movement_type', 'cost_adjustment')->sole();
    $promedio = inventarioPromedio($e, $e->laptop);

    expect((float) $lote->unit_cost)->toBe(12.0)
        ->and((float) $lote->total_cost)->toBe(120.0)
        ->and((float) $ajuste->total_cost)->toBe(20.0)
        ->and((float) $ajuste->quantity_in + (float) $ajuste->quantity_out)->toBe(0.0)
        ->and($ajuste->id_location)->toBe($e->a1->id_location)
        ->and((float) $promedio->inventory_value)->toBe(120.0)
        ->and((float) $promedio->average_unit_cost)->toBe(12.0)
        ->and((float) $e->laptop->fresh()->last_cost)->toBe(12.0);

    $this->get(route('inventory.index'))->assertOk()->assertSee('$120.00');
});

test('anular una compra recibida saca sus lotes, salvo que ya se hayan movido', function () {
    $e = inventarioEscenario();
    $this->actingAs($e->admin);
    $orden = inventarioOrden($e, $e->bodegaMatriz, [[$e->laptop, 10, 10], [$e->monitor, 4, 5]]);

    // Una compra cuyo lote ya se movió no se puede anular
    $this->post(route('purchases.store'), inventarioCompra($e, $orden, [[$orden->lineas[1], 4, 5, $e->a2]]))->assertSessionHasNoErrors();
    $movida = Purchase::queryAllBranches()->sole();
    $loteMovido = InventoryLot::queryAllBranches()->sole();
    InventoryMovement::queryAllBranches()->create([
        'id_product' => $e->monitor->id_product, 'id_inventory_lot' => $loteMovido->id_inventory_lot,
        'id_warehouse' => $e->bodegaMatriz->id_warehouse, 'movement_type' => 'transfer_out', 'movement_date' => now(),
        'quantity_out' => 1, 'unit_cost' => 5, 'total_cost' => 5,
    ]);
    $this->patch(route('purchases.updateStatus', $movida), ['status' => 'cancelled'])
        ->assertSessionHas('error', "No se puede anular la compra: el lote {$loteMovido->lot_code} ya tuvo movimientos de inventario.");
    expect($movida->fresh()->status)->toBe('received');

    // La otra sí: su lote sale completo y la empresa queda sin existencia de laptops
    $this->post(route('purchases.store'), inventarioCompra($e, $orden, [[$orden->lineas[0], 10, 10, $e->a1]]))->assertSessionHasNoErrors();
    $compra = Purchase::queryAllBranches()->latest('id_purchase')->first();
    $this->patch(route('purchases.updateStatus', $compra), ['status' => 'cancelled'])->assertSessionHas('success');

    $lote = InventoryLot::queryAllBranches()->where('id_product', $e->laptop->id_product)->sole();
    $salida = InventoryMovement::queryAllBranches()->where('movement_type', 'purchase_cancel')->sole();
    $promedio = inventarioPromedio($e, $e->laptop);

    expect($lote->status)->toBe('cancelled')
        ->and((float) $salida->quantity_out)->toBe(10.0)
        ->and($salida->id_location)->toBe($e->a1->id_location)
        ->and((float) $promedio->quantity_on_hand)->toBe(0.0)
        ->and((float) $promedio->inventory_value)->toBe(0.0)
        ->and((float) $promedio->average_unit_cost)->toBe(10.0);

    $this->get(route('inventory.index', ['search' => $e->laptop->sku]))->assertOk()->assertDontSee($lote->lot_code);
});

/** Usuario de la sucursal con los permisos indicados */
function inventarioUsuario(Branch $branch, array $permisos): User
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

test('lo comprado entra a Casa Matriz: la sucursal no ve existencias hasta que se las repartan', function () {
    $e = inventarioEscenario();
    $this->actingAs($e->admin);

    // La orden de la sucursal también entra a la bodega de Casa Matriz (sin elegirla, es la única)
    $ordenMatriz = inventarioOrden($e, $e->bodegaMatriz, [[$e->laptop, 3, 10]]);
    $ordenSucursal = inventarioOrden($e, $e->bodegaSucursal, [[$e->monitor, 2, 20]]);
    $this->post(route('purchases.store'), inventarioCompra($e, $ordenMatriz, [[$ordenMatriz->lineas[0], 3, 10, $e->a1]]))->assertSessionHasNoErrors();
    $this->post(route('purchases.store'), inventarioCompra($e, $ordenSucursal, [[$ordenSucursal->lineas[0], 2, 20, $e->a2]]))->assertSessionHasNoErrors();

    $productos = fn (User $user) => $this->actingAs($user)->get(route('inventory.index'))->assertOk()
        ->viewData('stock')->pluck('id_product')->sort()->values()->all();

    expect($productos(inventarioUsuario($e->sucursal, ['inventory.ver'])))->toBe([])
        ->and($productos(inventarioUsuario($e->matriz, ['inventory.ver'])))->toBe(collect([$e->laptop->id_product, $e->monitor->id_product])->sort()->values()->all());

    // Sin permiso no se ve
    $this->actingAs(inventarioUsuario($e->sucursal, ['purchases.ver']))->get(route('inventory.index'))->assertForbidden();
});

test('Casa Matriz registra la recepción de la orden de una sucursal y ambas ven la compra', function () {
    $e = inventarioEscenario();
    $orden = inventarioOrden($e, $e->bodegaSucursal, [[$e->monitor, 2, 20]]);
    $compras = inventarioUsuario($e->matriz, ['purchases.ver', 'purchases.crear']);
    $this->actingAs($compras);

    // A una bodega de la sucursal no puede entrar
    $this->post(route('purchases.store'), [...inventarioCompra($e, $orden, [[$orden->lineas[0], 2, 20, $e->s1]]), 'id_warehouse' => $e->bodegaSucursal->id_warehouse])
        ->assertSessionHasErrors(['id_warehouse' => 'La mercadería entra a una bodega de Casa Matriz (el departamento de compras), que luego la reparte a las sucursales.']);

    $this->post(route('purchases.store'), [...inventarioCompra($e, $orden, [[$orden->lineas[0], 2, 20, $e->a1]]), 'id_warehouse' => $e->bodegaMatriz->id_warehouse])
        ->assertSessionHasNoErrors();

    $compra = Purchase::queryAllBranches()->sole();
    expect($compra->id_branch)->toBe($e->sucursal->id_branch)
        ->and($compra->id_warehouse)->toBe($e->bodegaMatriz->id_warehouse)
        ->and(InventoryMovement::queryAllBranches()->sole()->id_location)->toBe($e->a1->id_location);

    // La compra es de la sucursal que pidió, pero la ve también el departamento de compras
    $this->get(route('purchases.show', $compra))->assertOk();
    $this->actingAs(inventarioUsuario($e->sucursal, ['purchases.ver']))->get(route('purchases.show', $compra))->assertOk();
});
