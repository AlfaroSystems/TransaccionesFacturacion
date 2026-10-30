<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\ExpenseType;
use App\Models\InventoryLot;
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
use App\Services\PurchaseOrderService;
use App\Services\PurchaseService;
use App\Services\RetaceoService;
use App\Support\Moneda;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

// =============================================================================
// Recepción de compras (puntos 5 y 6 de la auditoría): no se recibe más de lo
// pendiente ni productos fuera de la orden, y el estado de la orden se recalcula
// solo con las compras confirmadas (recibidas o completadas).
// =============================================================================

function recepcionEscenario(): object
{
    $tag = Str::upper(Str::random(5));
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::firstOrCreate(['name' => 'admin'])->id_role, ['assigned_at' => now()]);

    $company = Company::create(['name' => "Empresa {$tag}"]);
    // Casa Matriz: lo comprado entra a sus bodegas
    $branch = Branch::create(['id_company' => $company->id_company, 'name' => "Sucursal {$tag}", 'is_purchasing_department' => true]);
    $category = WarehouseCategory::create(['name' => "Categoría {$tag}"]);
    $warehouse = Warehouse::create(['id_branch' => $branch->id_branch, 'id_warehouse_category' => $category->id_warehouse_category, 'name' => "Bodega {$tag}"]);
    $unit = Unit::create(['name' => "Unidad {$tag}", 'abbreviation' => 'UND']);
    $laptop = Product::create(['name' => "Laptop {$tag}", 'sku' => 'L-'.$tag]);
    $monitor = Product::create(['name' => "Monitor {$tag}", 'sku' => 'M-'.$tag]);
    $ajeno = Product::create(['name' => "Mouse {$tag}", 'sku' => 'X-'.$tag]);
    $supplier = Supplier::create(['name' => "Proveedor {$tag}", 'email' => Str::lower($tag).'@example.com', 'country' => 'El Salvador', 'is_active' => true]);

    $order = PurchaseOrder::create([
        'id_supplier' => $supplier->id_supplier, 'id_branch' => $branch->id_branch, 'id_warehouse' => $warehouse->id_warehouse,
        'id_user' => $admin->id_user, 'order_date' => now(), 'expected_date' => now()->addWeek(),
        'currency' => 'USD', 'payment_terms' => 'Contado', 'status' => 'issued',
    ]);
    $linea = fn (Product $product, int $quantity) => PurchaseOrderDetail::create([
        'id_purchase_order' => $order->id_purchase_order, 'id_product' => $product->id_product,
        'quantity' => $quantity, 'id_unit' => $unit->id_unit, 'unit_price' => 10,
    ]);

    return (object) [
        'admin' => $admin, 'order' => $order, 'supplier' => $supplier,
        'laptop' => $laptop, 'monitor' => $monitor, 'ajeno' => $ajeno,
        'lineaLaptop' => $linea($laptop, 10), 'lineaMonitor' => $linea($monitor, 5),
    ];
}

function recepcionDatos(object $e, array $lineas, string $status = 'received'): array
{
    return [
        'id_purchase_order' => $e->order->id_purchase_order,
        'id_supplier' => $e->supplier->id_supplier,
        'purchase_date' => now()->toDateString(),
        'status' => $status,
        'details' => array_map(fn ($l) => [
            'id_purchase_order_detail' => $l[0]->id_purchase_order_detail,
            'id_product' => $l[0]->id_product,
            'quantity_received' => $l[1],
            'unit_price' => 10,
        ], $lineas),
    ];
}

test('no se puede recibir más de lo que falta de cada producto', function () {
    $e = recepcionEscenario();
    $this->actingAs($e->admin);

    $this->post(route('purchases.store'), recepcionDatos($e, [[$e->lineaLaptop, 11]]))
        ->assertSessionHasErrors(['details.0.quantity_received' => "De {$e->laptop->name} solo faltan 10 por recibir."]);

    $this->post(route('purchases.store'), recepcionDatos($e, [[$e->lineaLaptop, 6]]))->assertSessionHasNoErrors();

    // Ya llegaron 6: solo faltan 4
    $this->post(route('purchases.store'), recepcionDatos($e, [[$e->lineaLaptop, 5]]))
        ->assertSessionHasErrors(['details.0.quantity_received' => "De {$e->laptop->name} solo faltan 4 por recibir."]);
    $this->post(route('purchases.store'), recepcionDatos($e, [[$e->lineaLaptop, 4]]))->assertSessionHasNoErrors();

    expect(Purchase::count())->toBe(2);
});

test('no se pueden recibir productos que no están en la orden', function () {
    $e = recepcionEscenario();
    $this->actingAs($e->admin);

    // Sin línea de orden
    $datos = recepcionDatos($e, [[$e->lineaLaptop, 1]]);
    unset($datos['details'][0]['id_purchase_order_detail']);
    $this->post(route('purchases.store'), $datos)
        ->assertSessionHasErrors(['details.0.id_purchase_order_detail' => 'Solo se pueden recibir productos de la orden de compra.']);

    // Un producto distinto al de su línea de orden
    $datos = recepcionDatos($e, [[$e->lineaLaptop, 1]]);
    $datos['details'][0]['id_product'] = $e->ajeno->id_product;
    $this->post(route('purchases.store'), $datos)
        ->assertSessionHasErrors(['details.0.id_product' => 'El producto no corresponde a su línea de la orden de compra.']);

    // La misma línea dos veces
    $this->post(route('purchases.store'), recepcionDatos($e, [[$e->lineaLaptop, 1], [$e->lineaLaptop, 1]]))
        ->assertSessionHasErrors(['details.1.id_purchase_order_detail']);

    expect(Purchase::count())->toBe(0);
});

test('el borrador no cuenta como recibido y al confirmarlo se vuelve a revisar lo pendiente', function () {
    $e = recepcionEscenario();
    $this->actingAs($e->admin);

    // Dos borradores por las 10 laptops: ninguno cuenta todavía
    $this->post(route('purchases.store'), recepcionDatos($e, [[$e->lineaLaptop, 10]], 'draft'))->assertSessionHasNoErrors();
    $this->post(route('purchases.store'), recepcionDatos($e, [[$e->lineaLaptop, 10]], 'draft'))->assertSessionHasNoErrors();
    expect($e->order->fresh()->status)->toBe('issued');

    [$primero, $segundo] = Purchase::orderBy('id_purchase')->get();

    $this->patch(route('purchases.updateStatus', $primero), ['status' => 'received'])->assertSessionHas('success');
    expect($e->order->fresh()->status)->toBe('partial_received');

    // El segundo ya no cabe: las 10 se recibieron con el primero
    $this->patch(route('purchases.updateStatus', $segundo), ['status' => 'received'])->assertSessionHas('error');
    expect($segundo->fresh()->status)->toBe('draft');
});

test('el estado de la orden se recalcula al recibir, anular y eliminar compras', function () {
    $e = recepcionEscenario();
    $this->actingAs($e->admin);

    // Todo lo ordenado: completada
    $this->post(route('purchases.store'), recepcionDatos($e, [[$e->lineaLaptop, 10], [$e->lineaMonitor, 5]]))->assertSessionHasNoErrors();
    $completa = Purchase::latest('id_purchase')->first();
    expect($e->order->fresh()->status)->toBe('completed');

    // Ya no se puede recibir nada de una orden completada
    $this->post(route('purchases.store'), recepcionDatos($e, [[$e->lineaMonitor, 1]]))->assertSessionHasErrors('id_purchase_order');

    // Al anular la recepción, la orden vuelve a emitida
    $this->patch(route('purchases.updateStatus', $completa), ['status' => 'cancelled'])->assertSessionHas('success');
    expect($e->order->fresh()->status)->toBe('issued');

    // Recepción parcial; una recibida no vuelve a borrador, pero se puede anular
    $this->post(route('purchases.store'), recepcionDatos($e, [[$e->lineaMonitor, 2]]))->assertSessionHasNoErrors();
    $parcial = Purchase::latest('id_purchase')->first();
    expect($e->order->fresh()->status)->toBe('partial_received');

    $this->patch(route('purchases.updateStatus', $parcial), ['status' => 'draft'])->assertSessionHas('error');
    $this->patch(route('purchases.updateStatus', $parcial), ['status' => 'cancelled'])->assertSessionHas('success');
    expect($e->order->fresh()->status)->toBe('issued');

    // Eliminar un borrador deja la orden como estaba
    $this->post(route('purchases.store'), recepcionDatos($e, [[$e->lineaMonitor, 1]], 'draft'))->assertSessionHasNoErrors();
    $borrador = Purchase::latest('id_purchase')->first();
    $this->delete(route('purchases.destroy', $borrador))->assertSessionHas('success');
    expect(Purchase::find($borrador->id_purchase))->toBeNull()
        ->and($e->order->fresh()->status)->toBe('issued');
});

test('el formulario de recepción solo ofrece lo que falta recibir', function () {
    $e = recepcionEscenario();
    $this->actingAs($e->admin);

    $this->post(route('purchases.store'), recepcionDatos($e, [[$e->lineaLaptop, 10], [$e->lineaMonitor, 2]]))->assertSessionHasNoErrors();
    $this->post(route('purchases.store'), recepcionDatos($e, [[$e->lineaMonitor, 1]], 'draft'))->assertSessionHasNoErrors();

    // Laptop completa: no aparece. Monitor: faltan 3 (el borrador no cuenta)
    $detalles = $this->getJson(route('purchases.order-data', $e->order->id_purchase_order))->assertOk()->json('details');
    expect($detalles)->toHaveCount(1)
        ->and($detalles[0]['id_product'])->toBe($e->monitor->id_product)
        ->and((float) $detalles[0]['pending_quantity'])->toBe(3.0);
});

// =============================================================================
// Punto 7: transiciones de estado
// =============================================================================

test('la orden solo cambia de estado por los caminos permitidos', function () {
    $e = recepcionEscenario();
    $this->actingAs($e->admin);
    $cambiar = fn (string $status) => $this->patch(route('purchase_orders.updateStatus', $e->order), ['status' => $status]);

    // Recibida parcial y Completada no se ponen a mano: salen de lo recibido
    $cambiar('completed')->assertSessionHas('error', 'Una orden emitida no puede pasar a completada.');
    $cambiar('partial_received')->assertSessionHas('error');
    $cambiar('draft')->assertSessionHas('error');
    expect($e->order->fresh()->status)->toBe('issued');

    // Con un borrador registrado no se cancela
    $this->post(route('purchases.store'), recepcionDatos($e, [[$e->lineaLaptop, 1]], 'draft'))->assertSessionHasNoErrors();
    $cambiar('cancelled')->assertSessionHas('error', 'No se puede cancelar una orden con compras registradas: elimine o anule sus borradores.');

    // Con mercadería recibida tampoco: se cierra con lo recibido
    $borrador = Purchase::latest('id_purchase')->first();
    $this->patch(route('purchases.updateStatus', $borrador), ['status' => 'received'])->assertSessionHas('success');
    expect($e->order->fresh()->status)->toBe('partial_received');
    $cambiar('cancelled')->assertSessionHas('error');

    $cambiar('closed')->assertSessionHas('success');
    expect($e->order->fresh()->status)->toBe('closed');

    // Cerrada es final y ya no admite recepciones, ni se reabre al anular lo recibido
    $cambiar('issued')->assertSessionHas('error');
    $this->post(route('purchases.store'), recepcionDatos($e, [[$e->lineaMonitor, 1]]))->assertSessionHasErrors('id_purchase_order');
    $this->patch(route('purchases.updateStatus', $borrador), ['status' => 'cancelled'])->assertSessionHas('success');
    expect($e->order->fresh()->status)->toBe('closed');
});

test('una orden sin compras se puede cancelar y queda así', function () {
    $e = recepcionEscenario();
    $this->actingAs($e->admin);

    $this->patch(route('purchase_orders.updateStatus', $e->order), ['status' => 'cancelled'])->assertSessionHas('success');
    $this->patch(route('purchase_orders.updateStatus', $e->order), ['status' => 'issued'])
        ->assertSessionHas('error', 'Una orden cancelada no puede pasar a emitida.');
    expect($e->order->fresh()->status)->toBe('cancelled');
});

test('una compra completada es definitiva y una con retaceo activo no se anula', function () {
    $e = recepcionEscenario();
    $this->actingAs($e->admin);
    $cambiar = fn (Purchase $purchase, string $status) => $this->patch(route('purchases.updateStatus', $purchase), ['status' => $status]);

    $this->post(route('purchases.store'), recepcionDatos($e, [[$e->lineaLaptop, 2]], 'completed'))->assertSessionHasNoErrors();
    $completada = Purchase::latest('id_purchase')->first();
    $cambiar($completada, 'cancelled')->assertSessionHas('error', 'Una compra completada no puede pasar a anulada.');
    $cambiar($completada, 'draft')->assertSessionHas('error');
    expect($completada->fresh()->status)->toBe('completed');

    $this->post(route('purchases.store'), recepcionDatos($e, [[$e->lineaLaptop, 2]]))->assertSessionHasNoErrors();
    $recibida = Purchase::latest('id_purchase')->first();
    Retaceo::create(['id_supplier' => $e->supplier->id_supplier, 'id_purchase' => $recibida->id_purchase, 'retaceo_date' => now(), 'status' => 'draft']);

    $cambiar($recibida, 'cancelled')
        ->assertSessionHas('error', 'No se puede anular una compra con un retaceo activo: cancele primero el retaceo.');
    $cambiar($recibida, 'completed')->assertSessionHas('success');
});

test('el retaceo va de borrador a liquidado y aplicado, y se cancela antes de aplicarse', function () {
    $e = recepcionEscenario();
    $this->actingAs($e->admin);

    $this->post(route('purchases.store'), recepcionDatos($e, [[$e->lineaLaptop, 2]]))->assertSessionHasNoErrors();
    $purchase = Purchase::latest('id_purchase')->first();
    $retaceo = Retaceo::create(['id_supplier' => $e->supplier->id_supplier, 'id_purchase' => $purchase->id_purchase, 'retaceo_date' => now(), 'status' => 'draft']);
    $cambiar = fn (string $status) => $this->patch(route('retaceos.updateStatus', $retaceo), ['status' => $status]);

    $cambiar('applied')->assertSessionHas('error', 'Un retaceo borrador no puede pasar a aplicado.');
    $cambiar('calculated')->assertSessionHas('success');
    $cambiar('draft')->assertSessionHas('error');
    $cambiar('applied')->assertSessionHas('success');
    $cambiar('cancelled')->assertSessionHas('error', 'Un retaceo aplicado no puede pasar a cancelado.');
    expect($retaceo->fresh()->status)->toBe('applied');
});

// =============================================================================
// Punto 9: último costo de los productos
// =============================================================================

/** Gastos de la orden: $30 forman parte del costo y $20 no. La orden vale 10 × $10 + 5 × $10 = $150 */
function costoConGastos(object $e): void
{
    $e->order->update(['subtotal' => 150, 'discount' => 0]);
    $tipo = ExpenseType::create(['name' => 'Flete '.Str::random(5)]);
    $e->order->expenses()->createMany([
        ['id_expense_type' => $tipo->id_expense_type, 'amount' => 30, 'is_costable' => true],
        ['id_expense_type' => $tipo->id_expense_type, 'amount' => 20, 'is_costable' => false],
    ]);
}

test('al completar una compra se fija el último costo con los gastos que son costo', function () {
    $e = recepcionEscenario();
    costoConGastos($e);
    $this->actingAs($e->admin);

    $datos = recepcionDatos($e, [[$e->lineaLaptop, 10], [$e->lineaMonitor, 5]], 'completed');
    $datos['details'][1]['discount'] = 5;
    $this->post(route('purchases.store'), $datos)->assertSessionHasNoErrors();

    // Laptop: $100 + 30 × 100/150 = $120 entre 10 → $12
    // Monitor: ($50 − $5) + 30 × 45/150 = $54 entre 5 → $10.80
    $lineaLaptop = Purchase::latest('id_purchase')->first()->details()->where('id_product', $e->laptop->id_product)->first();
    expect((float) $lineaLaptop->unit_cost)->toBe(12.0)
        ->and((float) $e->laptop->fresh()->last_cost)->toBe(12.0)
        ->and($e->laptop->fresh()->id_last_cost_purchase_detail)->toBe($lineaLaptop->id_purchase_detail)
        ->and((float) $e->monitor->fresh()->last_cost)->toBe(10.8);
});

test('una compra recibida no fija el costo hasta completarse', function () {
    $e = recepcionEscenario();
    $this->actingAs($e->admin);

    $this->post(route('purchases.store'), recepcionDatos($e, [[$e->lineaLaptop, 4]]))->assertSessionHasNoErrors();
    $purchase = Purchase::latest('id_purchase')->first();
    expect($e->laptop->fresh()->last_cost)->toBeNull();

    $this->patch(route('purchases.updateStatus', $purchase), ['status' => 'completed'])->assertSessionHas('success');
    expect((float) $e->laptop->fresh()->last_cost)->toBe(10.0);
});

test('el retaceo aplicado reemplaza el costo de la factura', function () {
    $e = recepcionEscenario();
    $this->actingAs($e->admin);

    $this->post(route('purchases.store'), recepcionDatos($e, [[$e->lineaLaptop, 10]], 'completed'))->assertSessionHasNoErrors();
    $purchase = Purchase::latest('id_purchase')->first();
    $linea = $purchase->details()->first();
    expect((float) $e->laptop->fresh()->last_cost)->toBe(10.0);

    // FOB $100 + flete $20 + DAI $10 = $130 entre 10 → $13
    $this->post(route('retaceos.store'), [
        'id_purchase' => $purchase->id_purchase, 'id_supplier' => $e->supplier->id_supplier,
        'retaceo_date' => now()->toDateString(), 'total_freight' => 20, 'total_expenses' => 0, 'total_dai' => 10, 'status' => 'calculated',
        'details' => [[
            'id_product' => $e->laptop->id_product, 'id_purchase_detail' => $linea->id_purchase_detail,
            'quantity' => 10, 'cost_fob' => 100,
        ]],
    ])->assertSessionHasNoErrors();
    $retaceo = Retaceo::latest('id_retaceo')->first();

    // Liquidado todavía no cambia el costo
    expect((float) $e->laptop->fresh()->last_cost)->toBe(10.0);

    $this->patch(route('retaceos.updateStatus', $retaceo), ['status' => 'applied'])->assertSessionHas('success');
    expect((float) $e->laptop->fresh()->last_cost)->toBe(13.0)
        ->and((float) $linea->fresh()->unit_cost)->toBe(13.0);
});

test('solo la compra más reciente fija el último costo', function () {
    $e = recepcionEscenario();
    $this->actingAs($e->admin);

    $this->post(route('purchases.store'), recepcionDatos($e, [[$e->lineaLaptop, 4]], 'completed'))->assertSessionHasNoErrors();

    // Una compra con fecha anterior, registrada después, guarda su costo pero no cambia el del producto
    $anterior = recepcionDatos($e, [[$e->lineaLaptop, 4]], 'completed');
    $anterior['purchase_date'] = now()->subDays(3)->toDateString();
    $anterior['details'][0]['unit_price'] = 8;
    $this->post(route('purchases.store'), $anterior)->assertSessionHasNoErrors();

    expect((float) $e->laptop->fresh()->last_cost)->toBe(10.0)
        ->and((float) Purchase::latest('id_purchase')->first()->details()->first()->unit_cost)->toBe(8.0);
});

test('cada gasto de la orden indica si forma parte del costo', function () {
    $e = recepcionEscenario();
    $this->actingAs($e->admin);
    $tipo = ExpenseType::create(['name' => 'Flete '.Str::random(5)]);

    $this->post(route('purchase_orders.store'), [
        'id_supplier' => $e->supplier->id_supplier, 'id_branch' => $e->order->id_branch, 'id_warehouse' => $e->order->id_warehouse,
        'order_date' => now()->toDateString(), 'expected_date' => now()->addWeek()->toDateString(),
        'currency' => 'USD', 'payment_terms' => 'Contado',
        'products' => [['id_product' => $e->laptop->id_product, 'quantity' => 1, 'id_unit' => $e->lineaLaptop->id_unit, 'unit_price' => 10]],
        'expenses' => [
            ['id_expense_type' => $tipo->id_expense_type, 'description' => 'Flete', 'amount' => 30, 'is_costable' => '1'],
            ['id_expense_type' => $tipo->id_expense_type, 'description' => 'Comisión', 'amount' => 20, 'is_costable' => '0'],
            ['id_expense_type' => $tipo->id_expense_type, 'description' => 'Seguro', 'amount' => 5],
        ],
    ])->assertSessionHasNoErrors();

    $gastos = PurchaseOrder::latest('id_purchase_order')->first()->expenses()->orderBy('amount')->pluck('is_costable', 'description');
    expect($gastos->all())->toBe(['Seguro' => true, 'Comisión' => false, 'Flete' => true]);
});

test('el retaceo sugiere los gastos de la orden que son costo', function () {
    $e = recepcionEscenario();
    costoConGastos($e);
    $this->actingAs($e->admin);

    $this->post(route('purchases.store'), recepcionDatos($e, [[$e->lineaLaptop, 10]]))->assertSessionHasNoErrors();
    $purchase = Purchase::latest('id_purchase')->first();

    // Lo recibido vale $100 de los $150 de la orden: le tocan $20 de los $30
    $this->getJson(route('retaceos.purchase-data', $purchase->id_purchase))->assertJsonPath('suggested_expenses', fn ($v) => (float) $v === 20.0);
});

// =============================================================================
// Cambios simultáneos: cada cambio vuelve a leer el estado, con la fila bloqueada, dentro de
// su transacción. Las pruebas cargan el documento como lo haría la página y, antes de usarlo,
// otro usuario lo cambia; el cambio con el estado viejo debe rechazarse.
// =============================================================================

test('una compra anulada por otro usuario no se completa con el estado que se leyó antes', function () {
    $e = recepcionEscenario();
    $this->actingAs($e->admin);
    $this->post(route('purchases.store'), recepcionDatos($e, [[$e->lineaLaptop, 2]]))->assertSessionHasNoErrors();
    $abierta = Purchase::latest('id_purchase')->first();

    // Mientras tanto otro usuario la anula y su mercadería sale del inventario
    app(PurchaseService::class)->cambiarEstado(Purchase::find($abierta->id_purchase), 'cancelled');

    expect(fn () => app(PurchaseService::class)->cambiarEstado($abierta, 'completed'))
        ->toThrow(InvalidArgumentException::class, 'Una compra anulada no puede pasar a completada.');
    expect($abierta->fresh()->status)->toBe('cancelled')
        ->and(InventoryLot::queryAllBranches()->where('status', InventoryLot::STATUS_ACTIVE)->count())->toBe(0);
});

test('no se edita ni se elimina una compra que otro usuario ya confirmó', function () {
    $e = recepcionEscenario();
    $this->actingAs($e->admin);
    $this->post(route('purchases.store'), recepcionDatos($e, [[$e->lineaLaptop, 2]], 'draft'))->assertSessionHasNoErrors();
    $id = Purchase::latest('id_purchase')->value('id_purchase');
    [$paraEditar, $paraEliminar] = [Purchase::find($id), Purchase::find($id)];

    app(PurchaseService::class)->cambiarEstado(Purchase::find($id), 'received');

    expect(fn () => app(PurchaseService::class)->actualizar($paraEditar, recepcionDatos($e, [[$e->lineaLaptop, 5]])))
        ->toThrow(InvalidArgumentException::class, 'Solo se pueden editar compras en estado borrador.')
        ->and(fn () => app(PurchaseService::class)->eliminar($paraEliminar))
        ->toThrow(InvalidArgumentException::class, 'Solo se pueden eliminar compras en borrador.');

    $compra = Purchase::find($id);
    expect($compra->status)->toBe('received')
        ->and((float) $compra->details()->sole()->quantity_received)->toBe(2.0);
});

test('un retaceo cancelado o liquidado por otro usuario no se aplica ni se elimina con el estado viejo', function () {
    $e = recepcionEscenario();
    $this->actingAs($e->admin);
    $this->post(route('purchases.store'), recepcionDatos($e, [[$e->lineaLaptop, 2]]))->assertSessionHasNoErrors();
    $purchase = Purchase::latest('id_purchase')->first();
    $nuevo = fn (string $status) => Retaceo::create([
        'id_supplier' => $e->supplier->id_supplier, 'id_purchase' => $purchase->id_purchase,
        'retaceo_date' => now(), 'status' => $status,
    ]);

    $liquidado = $nuevo('calculated');
    app(RetaceoService::class)->cambiarEstado(Retaceo::find($liquidado->id_retaceo), 'cancelled');
    expect(fn () => app(RetaceoService::class)->cambiarEstado($liquidado, 'applied'))
        ->toThrow(InvalidArgumentException::class, 'Un retaceo cancelado no puede pasar a aplicado.');
    expect($liquidado->fresh()->status)->toBe('cancelled');

    $borrador = $nuevo('draft');
    app(RetaceoService::class)->cambiarEstado(Retaceo::find($borrador->id_retaceo), 'calculated');
    expect(fn () => app(RetaceoService::class)->eliminar($borrador))
        ->toThrow(InvalidArgumentException::class, 'Solo se pueden eliminar retaceos en borrador.');
    expect($borrador->fresh())->not->toBeNull();
});

test('una compra no tiene dos retaceos activos aunque la validación del formulario ya haya pasado', function () {
    $e = recepcionEscenario();
    $this->actingAs($e->admin);
    $this->post(route('purchases.store'), recepcionDatos($e, [[$e->lineaLaptop, 2]]))->assertSessionHasNoErrors();
    $purchase = Purchase::latest('id_purchase')->first();
    $datos = [
        'id_purchase' => $purchase->id_purchase, 'id_supplier' => $e->supplier->id_supplier,
        'retaceo_date' => now()->toDateString(), 'status' => 'draft', 'total_freight' => 10,
        'details' => [[
            'id_product' => $e->laptop->id_product, 'id_purchase_detail' => $purchase->details()->value('id_purchase_detail'),
            'quantity' => 2, 'cost_fob' => 20,
        ]],
    ];
    $activos = fn () => Retaceo::where('id_purchase', $purchase->id_purchase)->where('status', '!=', 'cancelled')->count();

    // Dos envíos que pasaron la validación a la vez: el segundo lo rechaza el servicio
    app(RetaceoService::class)->crear($datos);
    expect(fn () => app(RetaceoService::class)->crear($datos))->toThrow(ValidationException::class);

    // Y la base lo impide por cualquier otro camino; con el anterior cancelado, sí se crea
    $insertar = fn () => DB::transaction(fn () => Retaceo::create([
        'id_supplier' => $e->supplier->id_supplier, 'id_purchase' => $purchase->id_purchase, 'retaceo_date' => now(), 'status' => 'draft',
    ]));
    expect($insertar)->toThrow(QueryException::class);

    Retaceo::where('id_purchase', $purchase->id_purchase)->update(['status' => 'cancelled']);
    $insertar();
    expect($activos())->toBe(1);
});

test('una orden cancelada por otro usuario no se emite, edita ni elimina con el estado viejo', function () {
    $e = recepcionEscenario();
    $this->actingAs($e->admin);
    $e->order->update(['status' => 'draft']);
    $id = $e->order->id_purchase_order;
    [$paraEmitir, $paraEditar, $paraEliminar] = [PurchaseOrder::find($id), PurchaseOrder::find($id), PurchaseOrder::find($id)];

    app(PurchaseOrderService::class)->cambiarEstado(PurchaseOrder::find($id), 'cancelled');

    $servicio = app(PurchaseOrderService::class);
    expect(fn () => $servicio->cambiarEstado($paraEmitir, 'issued'))
        ->toThrow(InvalidArgumentException::class, 'Una orden cancelada no puede pasar a emitida.')
        // Se rechaza antes de usar los datos del formulario
        ->and(fn () => $servicio->actualizar($paraEditar, []))
        ->toThrow(InvalidArgumentException::class, 'Solo se pueden editar órdenes en borrador.')
        ->and(fn () => $servicio->eliminar($paraEliminar))
        ->toThrow(InvalidArgumentException::class, 'Solo se pueden eliminar órdenes en borrador.');

    expect(PurchaseOrder::find($id)->status)->toBe('cancelled');
});

test('la moneda es USD por defecto y se puede elegir otra de las disponibles', function () {
    $e = recepcionEscenario();
    $this->actingAs($e->admin);

    foreach (['purchase_orders.store', 'purchases.store', 'purchase-quotations.store'] as $ruta) {
        $this->post(route($ruta), ['currency' => 'XYZ'])
            ->assertSessionHasErrors(['currency' => Moneda::MENSAJE]);
    }

    $orden = fn (array $cambios = []) => [
        'id_supplier' => $e->supplier->id_supplier, 'id_branch' => $e->order->id_branch, 'id_warehouse' => $e->order->id_warehouse,
        'order_date' => now()->toDateString(), 'expected_date' => now()->addWeek()->toDateString(), 'payment_terms' => 'Contado',
        'products' => [['id_product' => $e->laptop->id_product, 'quantity' => 1, 'id_unit' => $e->lineaLaptop->id_unit, 'unit_price' => 10]],
        ...$cambios,
    ];

    // Sin indicarla queda USD; también se puede elegir otra
    $this->post(route('purchase_orders.store'), $orden())->assertSessionHasNoErrors();
    expect(PurchaseOrder::latest('id_purchase_order')->value('currency'))->toBe('USD');

    $this->post(route('purchase_orders.store'), $orden(['currency' => 'EUR']))->assertSessionHasNoErrors();
    expect(PurchaseOrder::latest('id_purchase_order')->value('currency'))->toBe('EUR');

    // La compra sin moneda toma la de su orden
    $e->order->update(['currency' => 'NIO']);
    $this->post(route('purchases.store'), recepcionDatos($e, [[$e->lineaLaptop, 1]]))->assertSessionHasNoErrors();
    expect(Purchase::latest('id_purchase')->value('currency'))->toBe('NIO');
});
