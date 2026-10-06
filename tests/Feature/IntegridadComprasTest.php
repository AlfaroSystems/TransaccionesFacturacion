<?php

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\PurchaseQuotation;
use App\Models\PurchaseQuotationRequest;
use App\Models\PurchaseRequest;
use App\Models\Retaceo;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseCategory;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// =============================================================================
// Escenario: un administrador y una orden emitida con una compra en el estado indicado
// =============================================================================

function escenarioCompras(string $estadoCompra = 'received'): object
{
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::firstOrCreate(['name' => 'admin'])->id, ['assigned_at' => now()]);

    $company   = Company::create(['name' => 'Distribuidora de Prueba']);
    $branch    = Branch::create(['company_id' => $company->id, 'name' => 'Sucursal Central']);
    $category  = WarehouseCategory::create(['name' => 'General']);
    $warehouse = Warehouse::create(['branch_id' => $branch->id, 'warehouse_category_id' => $category->id, 'name' => 'Bodega Central']);
    $unit      = Unit::create(['name' => 'Unidad', 'abbreviation' => 'UND']);
    $product   = Product::create(['name' => 'Laptop', 'sku' => 'SKU-'.Str::random(6)]);
    $supplier  = Supplier::create(['name' => 'Proveedor', 'email' => 'p@example.com', 'country' => 'El Salvador', 'is_active' => true]);

    $order = PurchaseOrder::create([
        'id_supplier' => $supplier->id_supplier, 'id_branch' => $branch->id, 'id_warehouse' => $warehouse->id,
        'id_user' => $admin->id, 'order_date' => now(), 'expected_date' => now()->addWeek(),
        'currency' => 'USD', 'payment_terms' => 'Contado', 'status' => 'issued',
    ]);
    $orderDetail = PurchaseOrderDetail::create([
        'id_purchase_order' => $order->id_purchase_order, 'id_product' => $product->id,
        'quantity' => 10, 'id_unit' => $unit->id, 'unit_price' => 50,
    ]);

    $purchase = Purchase::create([
        'id_purchase_order' => $order->id_purchase_order, 'id_supplier' => $supplier->id_supplier,
        'id_branch' => $branch->id, 'id_warehouse' => $warehouse->id, 'purchase_date' => now(), 'status' => $estadoCompra,
    ]);
    $purchaseDetail = PurchaseDetail::create([
        'id_purchase' => $purchase->id_purchase, 'id_purchase_order_detail' => $orderDetail->id_purchase_order_detail,
        'id_product' => $product->id, 'quantity_received' => 10, 'unit_price' => 50,
    ]);

    return (object) compact('admin', 'company', 'branch', 'warehouse', 'unit', 'product', 'supplier', 'order', 'orderDetail', 'purchase', 'purchaseDetail');
}

function datosRetaceo(object $e, array $cambios = []): array
{
    return array_merge([
        'id_purchase'    => $e->purchase->id_purchase,
        'id_supplier'    => $e->supplier->id_supplier,
        'retaceo_date'   => now()->toDateString(),
        'status'         => 'calculated',
        'total_freight'  => 100,
        'total_expenses' => 20,
        'details'        => [[
            'id_product' => $e->product->id, 'id_purchase_detail' => $e->purchaseDetail->id_purchase_detail,
            'quantity' => 10, 'cost_fob' => 500, 'dai_amount' => 25,
        ]],
    ], $cambios);
}

// =============================================================================
// Punto 9: retaceos
// =============================================================================

test('un retaceo solo se puede calcular sobre una compra recibida', function () {
    $e = escenarioCompras('draft');

    $this->actingAs($e->admin)->post(route('retaceos.store'), datosRetaceo($e))
        ->assertSessionHasErrors(['id_purchase']);

    expect(Retaceo::count())->toBe(0);
});

test('una compra no puede tener dos retaceos activos', function () {
    $e = escenarioCompras();
    $this->actingAs($e->admin);

    $this->post(route('retaceos.store'), datosRetaceo($e))->assertSessionHasNoErrors();
    $this->post(route('retaceos.store'), datosRetaceo($e))->assertSessionHasErrors(['id_purchase']);

    // Al cancelar el primero, se puede registrar uno nuevo
    Retaceo::first()->update(['status' => 'cancelled']);
    $this->post(route('retaceos.store'), datosRetaceo($e))->assertSessionHasNoErrors();

    expect(Retaceo::count())->toBe(2);
});

test('el retaceo guarda el prorrateo calculado en el servidor', function () {
    $e = escenarioCompras();

    $this->actingAs($e->admin)->post(route('retaceos.store'), datosRetaceo($e))->assertSessionHasNoErrors();

    $retaceo = Retaceo::with('details')->first();
    expect((float) $retaceo->total_fob)->toBe(500.0)
        ->and((float) $retaceo->total_cost)->toBe(645.0)          // 500 + 100 + 20 + 25
        ->and((float) $retaceo->details[0]->unit_cost)->toBe(64.5);
});

test('un retaceo aplicado es definitivo', function () {
    $e = escenarioCompras();
    $retaceo = Retaceo::create([
        'id_supplier' => $e->supplier->id_supplier, 'id_purchase' => $e->purchase->id_purchase,
        'retaceo_date' => now(), 'status' => 'applied',
    ]);

    $this->actingAs($e->admin)
        ->patch(route('retaceos.updateStatus', $retaceo), ['status' => 'draft'])
        ->assertSessionHas('error');

    expect($retaceo->fresh()->status)->toBe('applied');
});

// =============================================================================
// Punto 10: el dashboard no inventa datos
// =============================================================================

test('sin órdenes en los últimos meses el gráfico muestra ceros y no datos de ejemplo', function () {
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::firstOrCreate(['name' => 'admin'])->id, ['assigned_at' => now()]);

    $data = $this->actingAs($admin)->get(route('dashboard'))->assertOk()->viewData('chartData');

    expect($data)->toBe([0, 0, 0, 0, 0, 0]);
});

// =============================================================================
// Punto 11: los documentos no se borran en cascada
// =============================================================================

test('la base de datos impide borrar datos de los que dependen documentos de compra', function () {
    $e = escenarioCompras();
    Retaceo::create(['id_supplier' => $e->supplier->id_supplier, 'id_purchase' => $e->purchase->id_purchase, 'retaceo_date' => now()]);

    $borrar = fn (string $tabla, string $columna, $id) => fn () => DB::transaction(
        fn () => DB::table($tabla)->where($columna, $id)->delete()
    );

    expect($borrar('suppliers', 'id_supplier', $e->supplier->id_supplier))->toThrow(QueryException::class)
        ->and($borrar('purchase_orders', 'id_purchase_order', $e->order->id_purchase_order))->toThrow(QueryException::class)
        ->and($borrar('purchases', 'id_purchase', $e->purchase->id_purchase))->toThrow(QueryException::class)
        ->and($borrar('warehouses', 'id', $e->warehouse->id))->toThrow(QueryException::class)
        ->and($borrar('branches', 'id', $e->branch->id))->toThrow(QueryException::class)
        ->and($borrar('companies', 'id', $e->company->id))->toThrow(QueryException::class);

    expect(Retaceo::count())->toBe(1)
        ->and(Purchase::count())->toBe(1)
        ->and(PurchaseOrder::count())->toBe(1);
});

test('borrar un documento con documentos posteriores muestra un aviso en lugar de fallar', function () {
    $e = escenarioCompras('draft');
    $e->order->update(['status' => 'draft']);
    Retaceo::create(['id_supplier' => $e->supplier->id_supplier, 'id_purchase' => $e->purchase->id_purchase, 'retaceo_date' => now()]);
    $this->actingAs($e->admin);

    $this->delete(route('purchases.destroy', $e->purchase))->assertSessionHas('error');
    $this->delete(route('purchase_orders.destroy', $e->order))->assertSessionHas('error');

    $request = PurchaseRequest::create([
        'uuid' => (string) Str::uuid(), 'purchase_request_code' => 'REQ-PRUEBA', 'id_branch' => $e->branch->id,
        'id_warehouse' => $e->warehouse->id, 'id_user' => $e->admin->id, 'request_date' => now(),
        'required_date' => now(), 'justification' => 'x', 'status' => 'draft',
    ]);
    $quotationRequest = PurchaseQuotationRequest::create(['id_purchase_request' => $request->id_purchase_request]);
    $this->delete(route('purchase-requests.destroy', $request))->assertSessionHas('error');

    $quotation = PurchaseQuotation::create([
        'id_purchase_quotation_request' => $quotationRequest->id_purchase_quotation_request,
        'id_supplier' => $e->supplier->id_supplier, 'quotation_date' => now(),
    ]);
    $e->order->update(['id_purchase_quotation' => $quotation->id_purchase_quotation]);
    $this->delete(route('purchase-quotations.destroy', $quotation->id_purchase_quotation))->assertSessionHas('error');

    expect(Purchase::count())->toBe(1)
        ->and(PurchaseOrder::count())->toBe(1)
        ->and(PurchaseRequest::count())->toBe(1)
        ->and(PurchaseQuotation::count())->toBe(1);
});

// =============================================================================
// Punto 12: bitácora
// =============================================================================

test('la bitácora registra el modelo de cada cambio, incluidas las líneas reemplazadas', function () {
    $e = escenarioCompras('draft');
    $lineaAnterior = $e->purchaseDetail->id_purchase_detail;

    $this->actingAs($e->admin)->put(route('purchases.update', $e->purchase), [
        'id_supplier'   => $e->supplier->id_supplier,
        'purchase_date' => now()->toDateString(),
        'details'       => [['id_product' => $e->product->id, 'quantity_received' => 4, 'unit_price' => 50]],
    ])->assertSessionHasNoErrors();

    // La compra editada y la línea borrada quedan registradas con su modelo
    expect(AuditLog::where('auditable_type', Purchase::class)->where('id_record', $e->purchase->id_purchase)->where('action', 'update')->exists())->toBeTrue()
        ->and(AuditLog::where('auditable_type', PurchaseDetail::class)->where('id_record', $lineaAnterior)->whereNull('modified_data')->exists())->toBeTrue();

    $this->get(route('audit-logs.index', ['auditable_type' => PurchaseDetail::class]))
        ->assertOk()
        ->assertSee('PurchaseDetail');
});

test('al aceptar una oferta, el rechazo de las demás queda en la bitácora', function () {
    $e = escenarioCompras();
    $request = PurchaseRequest::create([
        'uuid' => (string) Str::uuid(), 'purchase_request_code' => 'REQ-OFERTAS', 'id_branch' => $e->branch->id,
        'id_warehouse' => $e->warehouse->id, 'id_user' => $e->admin->id, 'request_date' => now(),
        'required_date' => now(), 'justification' => 'x', 'status' => 'approved',
    ]);
    $quotationRequest = PurchaseQuotationRequest::create(['id_purchase_request' => $request->id_purchase_request]);
    [$aceptada, $rechazada] = collect([1, 2])->map(fn () => PurchaseQuotation::create([
        'id_purchase_quotation_request' => $quotationRequest->id_purchase_quotation_request,
        'id_supplier' => $e->supplier->id_supplier, 'quotation_date' => now(), 'status' => 'submitted',
    ]))->all();

    $this->actingAs($e->admin)
        ->patch(route('purchase-quotation-requests.select-quotation', [$quotationRequest, $aceptada]))
        ->assertSessionHas('success');

    expect($rechazada->fresh()->status)->toBe('rejected')
        ->and(AuditLog::where('auditable_type', PurchaseQuotation::class)->where('id_record', $rechazada->id_purchase_quotation)->where('action', 'selectQuotation')->exists())->toBeTrue();
});

// =============================================================================
// Punto 13: archivos SVG
// =============================================================================

test('no se aceptan imágenes SVG como logo ni como imagen de producto', function () {
    $e = escenarioCompras();
    $svg = fn () => UploadedFile::fake()->createWithContent('imagen.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

    $this->actingAs($e->admin)
        ->post(route('companies.store'), ['name' => 'Empresa SVG', 'logo' => $svg()])
        ->assertSessionHasErrors(['logo']);

    $this->post(route('products.store'), ['name' => 'Producto SVG', 'sku' => 'SVG-1', 'images' => [$svg()]])
        ->assertSessionHasErrors(['images.0']);
});

// =============================================================================
// Punto 14: topes de descuento e IVA
// =============================================================================

test('el descuento de una línea no puede superar su subtotal y el IVA no puede pasar de 100 %', function () {
    $e = escenarioCompras();
    $this->actingAs($e->admin);

    // Orden de compra: 5 x 10 = 50
    $this->post(route('purchase_orders.store'), [
        'id_supplier' => $e->supplier->id_supplier, 'id_branch' => $e->branch->id, 'id_warehouse' => $e->warehouse->id,
        'order_date' => now()->toDateString(), 'expected_date' => now()->addDay()->toDateString(),
        'currency' => 'USD', 'payment_terms' => 'Contado',
        'products' => [['id_product' => $e->product->id, 'id_unit' => $e->unit->id, 'quantity' => 5, 'unit_price' => 10, 'discount' => 60, 'tax_rate' => 150]],
    ])->assertSessionHasErrors(['products.0.discount', 'products.0.tax_rate']);

    // Compra: 2 x 50 = 100
    $this->post(route('purchases.store'), [
        'id_purchase_order' => $e->order->id_purchase_order, 'id_supplier' => $e->supplier->id_supplier,
        'purchase_date' => now()->toDateString(), 'status' => 'draft',
        'details' => [['id_product' => $e->product->id, 'quantity_received' => 2, 'unit_price' => 50, 'discount' => 100.01, 'tax_rate' => 101]],
    ])->assertSessionHasErrors(['details.0.discount', 'details.0.tax_rate']);

    // Un descuento igual al subtotal sí es válido
    $this->post(route('purchases.store'), [
        'id_purchase_order' => $e->order->id_purchase_order, 'id_supplier' => $e->supplier->id_supplier,
        'purchase_date' => now()->toDateString(), 'status' => 'draft',
        'details' => [['id_product' => $e->product->id, 'quantity_received' => 2, 'unit_price' => 50, 'discount' => 100, 'tax_rate' => 13]],
    ])->assertSessionHasNoErrors();

    // Oferta de proveedor: 1 x 20 = 20
    $request = PurchaseRequest::create([
        'uuid' => (string) Str::uuid(), 'purchase_request_code' => 'REQ-TOPES', 'id_branch' => $e->branch->id,
        'id_warehouse' => $e->warehouse->id, 'id_user' => $e->admin->id, 'request_date' => now(),
        'required_date' => now(), 'justification' => 'x', 'status' => 'approved',
    ]);
    $quotationRequest = PurchaseQuotationRequest::create(['id_purchase_request' => $request->id_purchase_request]);

    $this->post(route('purchase-quotations.store'), [
        'id_purchase_quotation_request' => $quotationRequest->id_purchase_quotation_request,
        'id_supplier' => $e->supplier->id_supplier, 'quotation_date' => now()->toDateString(),
        'items' => [['id_product' => $e->product->id, 'quantity' => 1, 'unit_price' => 20, 'discount' => 25, 'tax_rate' => 200]],
    ])->assertSessionHasErrors(['items.0.discount', 'items.0.tax_rate']);
});

// =============================================================================
// Punto 16: registro de una compra con sus totales
// =============================================================================

test('al registrar una compra se guardan sus totales y líneas calculados en el servidor', function () {
    $e = escenarioCompras();

    $this->actingAs($e->admin)->post(route('purchases.store'), [
        'id_purchase_order' => $e->order->id_purchase_order, 'id_supplier' => $e->supplier->id_supplier,
        'purchase_date' => now()->toDateString(), 'status' => 'draft',
        // Los totales que envíe el formulario se ignoran: se calculan en el servidor
        'total' => 1,
        'details' => [[
            'id_product' => $e->product->id, 'id_purchase_order_detail' => $e->orderDetail->id_purchase_order_detail,
            'quantity_received' => 4, 'unit_price' => 50, 'discount' => 20, 'tax_rate' => 13,
        ]],
    ])->assertSessionHasNoErrors();

    $compra = Purchase::with('details')->latest('id_purchase')->first();

    // 4 x 50 = 200, descuento 20 -> base 180, IVA 13 % = 23.40, total 203.40
    expect((float) $compra->subtotal)->toBe(200.0)
        ->and((float) $compra->discount)->toBe(20.0)
        ->and((float) $compra->tax)->toBe(23.4)
        ->and((float) $compra->total)->toBe(203.4)
        ->and($compra->details)->toHaveCount(1)
        ->and((float) $compra->details[0]->total)->toBe(203.4);
});
