<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseCategory;
use Illuminate\Support\Str;

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
    $branch = Branch::create(['id_company' => $company->id_company, 'name' => "Sucursal {$tag}"]);
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
    \App\Models\Retaceo::create(['id_supplier' => $e->supplier->id_supplier, 'id_purchase' => $recibida->id_purchase, 'retaceo_date' => now(), 'status' => 'draft']);

    $cambiar($recibida, 'cancelled')
        ->assertSessionHas('error', 'No se puede anular una compra con un retaceo activo: cancele primero el retaceo.');
    $cambiar($recibida, 'completed')->assertSessionHas('success');
});

test('el retaceo va de borrador a liquidado y aplicado, y se cancela antes de aplicarse', function () {
    $e = recepcionEscenario();
    $this->actingAs($e->admin);

    $this->post(route('purchases.store'), recepcionDatos($e, [[$e->lineaLaptop, 2]]))->assertSessionHasNoErrors();
    $purchase = Purchase::latest('id_purchase')->first();
    $retaceo = \App\Models\Retaceo::create(['id_supplier' => $e->supplier->id_supplier, 'id_purchase' => $purchase->id_purchase, 'retaceo_date' => now(), 'status' => 'draft']);
    $cambiar = fn (string $status) => $this->patch(route('retaceos.updateStatus', $retaceo), ['status' => $status]);

    $cambiar('applied')->assertSessionHas('error', 'Un retaceo borrador no puede pasar a aplicado.');
    $cambiar('calculated')->assertSessionHas('success');
    $cambiar('draft')->assertSessionHas('error');
    $cambiar('applied')->assertSessionHas('success');
    $cambiar('cancelled')->assertSessionHas('error', 'Un retaceo aplicado no puede pasar a cancelado.');
    expect($retaceo->fresh()->status)->toBe('applied');
});
