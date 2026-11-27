<?php

use App\Models\Branch;
use App\Models\CashShift;
use App\Models\Company;
use App\Models\Customer;
use App\Models\InventoryCostAverage;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\Location;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleCostDetail;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseCategory;
use App\Mail\SaleReceiptMail;
use App\Services\PriceService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

// =============================================================================
// Ventas: la entrega descuenta el inventario de la sucursal con PEPS (lote más antiguo
// primero), registra la salida en el Kardex y baja la existencia y el valor global.
// =============================================================================

function ventaUsuario(Branch $branch, array $permisos): User
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

/** Agrega un lote disponible con existencia en una ubicación, como lo deja una compra recibida */
function ventaLote(Product $product, Warehouse $warehouse, Location $location, float $qty, float $cost, $receivedAt): InventoryLot
{
    $lot = InventoryLot::create([
        'lot_code' => 'LOT-'.Str::upper(Str::random(6)),
        'id_product' => $product->id_product,
        'id_warehouse' => $warehouse->id_warehouse,
        'received_at' => $receivedAt,
        'quantity_received' => $qty,
        'unit_cost' => $cost,
        'total_cost' => $qty * $cost,
        'status' => InventoryLot::STATUS_ACTIVE,
        'placed_at' => now(),
    ]);

    InventoryMovement::create([
        'id_product' => $product->id_product,
        'id_inventory_lot' => $lot->id_inventory_lot,
        'id_warehouse' => $warehouse->id_warehouse,
        'id_inventory_location' => ubicacionInventario($location),
        'movement_type' => InventoryMovement::TYPE_PURCHASE_RECEIPT,
        'movement_date' => now(),
        'quantity_in' => $qty,
        'quantity_out' => 0,
        'unit_cost' => $cost,
        'total_cost' => $qty * $cost,
    ]);

    return $lot;
}

/** Sucursal con un producto (precio $20) y dos lotes: L1 (5 @ $5, más antiguo) y L2 (10 @ $8) */
function ventaEscenario(): object
{
    $tag = Str::upper(Str::random(5));
    $company = Company::create(['name' => "Empresa {$tag}"]);
    $sucursal = Branch::create(['id_company' => $company->id_company, 'name' => "Sucursal {$tag}"]);
    $category = WarehouseCategory::create(['name' => "Categoría {$tag}"]);
    $bodega = Warehouse::create(['id_branch' => $sucursal->id_branch, 'id_warehouse_category' => $category->id_warehouse_category, 'name' => "Bodega {$tag}"]);
    $a1 = Location::create(['id_warehouse' => $bodega->id_warehouse, 'code' => 'A1', 'capacity' => 1000]);
    $product = Product::create(['name' => "Producto {$tag}", 'sku' => 'P-'.$tag]);

    $l1 = ventaLote($product, $bodega, $a1, 5, 5, now()->subDays(2));
    $l2 = ventaLote($product, $bodega, $a1, 10, 8, now()->subDay());

    InventoryCostAverage::create([
        'id_company' => $company->id_company, 'id_product' => $product->id_product,
        'quantity_on_hand' => 15, 'inventory_value' => 105, 'average_unit_cost' => 7,
    ]);

    $presentation = $product->presentations()->create(['name' => 'Unidad', 'factor' => 1, 'is_active' => true]);
    (new PriceService)->cambiarPrecio($presentation, 20);

    $customer = Customer::create(['name' => "Cliente {$tag}", 'customer_type' => 'final', 'email' => 'cliente@example.com']);

    // El POS exige una caja abierta en la sucursal
    CashShift::create(['id_branch' => $sucursal->id_branch, 'opened_at' => now(), 'opening_amount' => 0, 'status' => CashShift::STATUS_OPEN]);

    return (object) [
        'company' => $company, 'sucursal' => $sucursal, 'bodega' => $bodega, 'a1' => $a1,
        'product' => $product, 'presentation' => $presentation, 'l1' => $l1, 'l2' => $l2, 'customer' => $customer,
        'vendedor' => ventaUsuario($sucursal, ['sales.ver', 'sales.crear']),
    ];
}

function ventaPayload(object $e, array $override = []): array
{
    return array_merge([
        'id_branch' => $e->sucursal->id_branch,
        'id_customer' => $e->customer->id_customer,
        'document_type' => 'invoice',
        'payment_method' => 'cash',
        'tax_rate' => 0,
        'lines' => [['id_product_presentation' => $e->presentation->id_product_presentation, 'quantity' => 7]],
    ], $override);
}

it('consume los lotes por PEPS y calcula el costo de venta', function () {
    Mail::fake();
    $e = ventaEscenario();

    $this->actingAs($e->vendedor)
        ->post(route('sales.store'), ventaPayload($e))
        ->assertRedirect();

    $sale = Sale::queryAllBranches()->latest('id_sale')->firstOrFail();
    expect($sale->status)->toBe(Sale::STATUS_DELIVERED);

    // 7 unidades: 5 del lote más antiguo (@5 = 25) + 2 del siguiente (@8 = 16) = 41
    expect((float) $sale->total_cost)->toBe(41.0);

    $detail = $sale->details()->firstOrFail();
    $costos = SaleCostDetail::where('id_sale_detail', $detail->id_sale_detail)
        ->orderBy('id_sales_cost_detail')->get();

    expect($costos)->toHaveCount(2);
    expect((int) $costos[0]->id_inventory_lot)->toBe($e->l1->id_inventory_lot); // PEPS: el más antiguo primero
    expect((float) $costos[0]->quantity)->toBe(5.0);
    expect((float) $costos[1]->id_inventory_lot)->toBe((float) $e->l2->id_inventory_lot);
    expect((float) $costos[1]->quantity)->toBe(2.0);
});

it('registra la salida en el Kardex y baja existencia y costo promedio', function () {
    Mail::fake();
    $e = ventaEscenario();

    $this->actingAs($e->vendedor)->post(route('sales.store'), ventaPayload($e))->assertRedirect();

    $sale = Sale::queryAllBranches()->latest('id_sale')->firstOrFail();

    $movs = InventoryMovement::queryAllBranches()
        ->where('reference_type', InventoryMovement::REFERENCE_SALE)
        ->where('reference_id', $sale->id_sale)->get();
    expect($movs)->toHaveCount(2);
    expect($movs->pluck('movement_type')->unique()->all())->toBe([InventoryMovement::TYPE_SALE_ISSUE]);

    $promedio = InventoryCostAverage::queryAllBranches()
        ->where('id_company', $e->company->id_company)->where('id_product', $e->product->id_product)->firstOrFail();
    expect((float) $promedio->quantity_on_hand)->toBe(8.0);   // 15 - 7
    expect((float) $promedio->inventory_value)->toBe(64.0);   // 105 - 41
});

it('no permite vender más que la existencia de la sucursal', function () {
    Mail::fake();
    $e = ventaEscenario();

    $this->actingAs($e->vendedor)
        ->post(route('sales.store'), ventaPayload($e, ['lines' => [['id_product_presentation' => $e->presentation->id_product_presentation, 'quantity' => 20]]]))
        ->assertSessionHasErrors('lines');

    expect(Sale::queryAllBranches()->count())->toBe(0);
});

it('no vende un producto sin precio asignado', function () {
    Mail::fake();
    $e = ventaEscenario();
    $otro = Product::create(['name' => 'Sin precio', 'sku' => 'SP-'.Str::upper(Str::random(5))]);
    $presOtro = $otro->presentations()->create(['name' => 'Unidad', 'factor' => 1, 'is_active' => true]);

    $this->actingAs($e->vendedor)
        ->post(route('sales.store'), ventaPayload($e, ['lines' => [['id_product_presentation' => $presOtro->id_product_presentation, 'quantity' => 1]]]))
        ->assertSessionHasErrors();

    expect(Sale::queryAllBranches()->count())->toBe(0);
});

it('envía el comprobante al correo del cliente al finalizar la venta', function () {
    Mail::fake();
    $e = ventaEscenario();

    $this->actingAs($e->vendedor)->post(route('sales.store'), ventaPayload($e))->assertRedirect();

    Mail::assertQueued(SaleReceiptMail::class);
    $sale = Sale::queryAllBranches()->latest('id_sale')->firstOrFail();
    expect($sale->emailed_at)->not->toBeNull();

    // El correo (plantilla Markdown) debe renderizar sin error (regresión "No hint path for [mail]")
    (new SaleReceiptMail($sale))->assertSeeInHtml($sale->sale_code);
});

it('acumula el total de la venta en el turno de caja abierto', function () {
    Mail::fake();
    $e = ventaEscenario();

    // 7 unidades a $20, IVA 0 -> total 140, pago en efectivo
    $this->actingAs($e->vendedor)->post(route('sales.store'), ventaPayload($e))->assertRedirect();

    $shift = CashShift::openForBranch($e->sucursal->id_branch);
    expect((float) $shift->total_sales)->toBe(140.0);
    expect((float) $shift->total_cash)->toBe(140.0);
    expect((float) $shift->total_card)->toBe(0.0);
});

it('no se puede vender sin una caja abierta', function () {
    Mail::fake();
    $e = ventaEscenario();
    CashShift::openForBranch($e->sucursal->id_branch)->update(['status' => CashShift::STATUS_CLOSED]);

    $this->actingAs($e->vendedor)->post(route('sales.store'), ventaPayload($e))
        ->assertSessionHasErrors('id_branch');

    expect(Sale::queryAllBranches()->count())->toBe(0);
});

it('en Crédito Fiscal guarda el snapshot fiscal del receptor', function () {
    Mail::fake();
    $e = ventaEscenario();
    $contribuyente = Customer::create([
        'name' => 'Empresa X, S.A. de C.V.', 'customer_type' => 'taxpayer', 'document_type' => 'nit',
        'document_number' => '0614-010101-101-1', 'nrc' => '123456-7', 'commercial_line' => 'Comercio', 'address' => 'San Salvador',
    ]);

    $this->actingAs($e->vendedor)->post(route('sales.store'), ventaPayload($e, [
        'id_customer' => $contribuyente->id_customer,
        'document_type' => 'ccf',
    ]))->assertRedirect();

    $sale = Sale::queryAllBranches()->latest('id_sale')->firstOrFail();
    expect($sale->receptor_business_name)->toBe('Empresa X, S.A. de C.V.');
    expect($sale->receptor_nrc)->toBe('123456-7');
    expect($sale->receptor_document_number)->toBe('0614-010101-101-1');
    expect($sale->receptor_line)->toBe('Comercio');
});

it('no permite Crédito Fiscal a un consumidor final', function () {
    Mail::fake();
    $e = ventaEscenario(); // el cliente del escenario es consumidor final

    $this->actingAs($e->vendedor)->post(route('sales.store'), ventaPayload($e, ['document_type' => 'ccf']))
        ->assertSessionHasErrors('id_customer');

    expect(Sale::queryAllBranches()->count())->toBe(0);
});

it('guarda los datos del pago en tarjeta y acumula en caja', function () {
    Mail::fake();
    $e = ventaEscenario();

    $this->actingAs($e->vendedor)->post(route('sales.store'), ventaPayload($e, [
        'payment_method' => 'credit_card',
        'payment_reference' => 'AUTH-123',
        'payment_bank' => 'Banco X',
    ]))->assertRedirect();

    $sale = Sale::queryAllBranches()->latest('id_sale')->firstOrFail();
    expect($sale->payment_method)->toBe('credit_card');
    expect($sale->payment_reference)->toBe('AUTH-123');
    expect($sale->payment_bank)->toBe('Banco X');

    $shift = CashShift::openForBranch($e->sucursal->id_branch);
    expect((float) $shift->total_card)->toBe(140.0);
    expect((float) $shift->total_cash)->toBe(0.0);
});

it('exige referencia en el pago con tarjeta', function () {
    Mail::fake();
    $e = ventaEscenario();

    $this->actingAs($e->vendedor)->post(route('sales.store'), ventaPayload($e, ['payment_method' => 'credit_card']))
        ->assertSessionHasErrors('payment_reference');
});

it('vende por presentación consumiendo cantidad × factor del inventario', function () {
    Mail::fake();
    $e = ventaEscenario(); // L1 5@5 y L2 10@8 = 15 u. base

    $caja = $e->product->presentations()->create(['name' => 'Caja', 'factor' => 5, 'is_active' => true]);
    (new PriceService)->cambiarPrecio($caja, 90);

    // 2 Cajas = 10 unidades base: PEPS 5@5 + 5@8 = 25 + 40 = 65 de costo; ingreso 2×90 = 180
    $this->actingAs($e->vendedor)->post(route('sales.store'), ventaPayload($e, [
        'tax_rate' => 0,
        'lines' => [['id_product_presentation' => $caja->id_product_presentation, 'quantity' => 2]],
    ]))->assertRedirect();

    $sale = Sale::queryAllBranches()->latest('id_sale')->firstOrFail();
    expect($sale->status)->toBe(Sale::STATUS_DELIVERED);
    expect((float) $sale->subtotal)->toBe(180.0);
    expect((float) $sale->total_cost)->toBe(65.0);
});

it('no vende lotes vencidos', function () {
    Mail::fake();
    $e = ventaEscenario(); // L1 5@5, L2 10@8
    $e->l1->update(['expiration_date' => now()->subDay()]);   // vencido: no debe venderse
    $e->l2->update(['expiration_date' => now()->addYear()]);

    // Vender 7: solo L2 (10) está disponible -> 7 × 8 = 56
    $this->actingAs($e->vendedor)->post(route('sales.store'), ventaPayload($e))->assertRedirect();

    $sale = Sale::queryAllBranches()->latest('id_sale')->firstOrFail();
    expect((float) $sale->total_cost)->toBe(56.0);
});

it('consume primero el lote que vence antes (FEFO)', function () {
    Mail::fake();
    $e = ventaEscenario(); // L1 recibido antes (@5), L2 recibido después (@8)
    $e->l1->update(['expiration_date' => now()->addYears(3)]); // vence lejos
    $e->l2->update(['expiration_date' => now()->addMonth()]);  // vence pronto

    // Vender 5: FEFO toma L2 primero -> 5 × 8 = 40
    $this->actingAs($e->vendedor)->post(route('sales.store'), ventaPayload($e, ['lines' => [['id_product_presentation' => $e->presentation->id_product_presentation, 'quantity' => 5]]]))->assertRedirect();

    $sale = Sale::queryAllBranches()->latest('id_sale')->firstOrFail();
    expect((float) $sale->total_cost)->toBe(40.0);

    $primerConsumo = \App\Models\SaleCostDetail::whereIn('id_sale_detail', $sale->details->pluck('id_sale_detail'))
        ->orderBy('id_sales_cost_detail')->first();
    expect((int) $primerConsumo->id_inventory_lot)->toBe($e->l2->id_inventory_lot);
});

it('el reporte de ventas por lote muestra los lotes vendidos', function () {
    Mail::fake();
    $e = ventaEscenario();
    $this->actingAs($e->vendedor)->post(route('sales.store'), ventaPayload($e))->assertRedirect(); // consume L1 y L2

    $viewer = ventaUsuario($e->sucursal, ['sales.rentabilidad']);
    $resp = $this->actingAs($viewer)->get(route('sales.report-lot'))->assertOk();

    $rows = collect($resp->viewData('rows'));
    expect($rows->pluck('id_inventory_lot')->all())->toContain($e->l1->id_inventory_lot, $e->l2->id_inventory_lot);
    expect((float) $resp->viewData('totalCosto'))->toBe(41.0); // 5@5 + 2@8
});

it('genera el ticket imprimible de la venta', function () {
    Mail::fake();
    $e = ventaEscenario();
    $this->actingAs($e->vendedor)->post(route('sales.store'), ventaPayload($e))->assertRedirect();

    $sale = Sale::queryAllBranches()->latest('id_sale')->firstOrFail();
    $this->get(route('sales.ticket', $sale))->assertOk()->assertSee($sale->sale_code);
});

it('la cartera muestra las compras y el total acumulado del cliente', function () {
    Mail::fake();
    $e = ventaEscenario();

    // Dos ventas entregadas del mismo cliente: 7×20 = 140 y 1×20 = 20 (IVA 0)
    $this->actingAs($e->vendedor)->post(route('sales.store'), ventaPayload($e))->assertRedirect();
    $this->post(route('sales.store'), ventaPayload($e, ['lines' => [['id_product_presentation' => $e->presentation->id_product_presentation, 'quantity' => 1]]]))->assertRedirect();

    $viewer = ventaUsuario($e->sucursal, ['customers.ver']);
    $resp = $this->actingAs($viewer)->get(route('customers.cartera'))->assertOk();

    $fila = collect($resp->viewData('customers')->items())->firstWhere('id_customer', $e->customer->id_customer);
    expect($fila)->not->toBeNull();
    expect((int) $fila->compras)->toBe(2);
    expect((float) $fila->total_acumulado)->toBe(160.0);
    expect($fila->ultima_compra)->not->toBeNull();
});

it('cambiar el precio cierra el vigente y abre uno nuevo (historial)', function () {
    $tag = Str::upper(Str::random(5));
    $product = Product::create(['name' => "Prod {$tag}", 'sku' => 'PH-'.$tag]);
    $pres = $product->presentations()->create(['name' => 'Unidad', 'factor' => 1, 'is_active' => true]);

    $svc = new PriceService;
    $svc->cambiarPrecio($pres, 10, 'inicial');
    $svc->cambiarPrecio($pres, 12.5, 'ajuste');

    $historial = $pres->prices()->orderBy('valid_from')->get();
    expect($historial)->toHaveCount(2);
    expect($historial->whereNull('valid_until')->count())->toBe(1);
    expect((float) $pres->precioVigente())->toBe(12.5);
    expect($historial->firstWhere('valid_until', '!=', null))->not->toBeNull(); // el anterior quedó cerrado
});
