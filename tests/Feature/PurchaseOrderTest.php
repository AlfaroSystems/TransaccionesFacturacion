<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\ExpenseType;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseCategory;
use App\Services\PurchaseOrderService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

// =============================================================================
// Helpers compartidos para montar datos maestros en cada test
// =============================================================================

function setupDatosMaestros(): array
{
    $user = User::factory()->create();

    // Asignar rol admin para que Gate::before() otorgue acceso total en las pruebas
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $user->roles()->syncWithoutDetaching([$adminRole->id => ['assigned_at' => now()]]);
    // Recargar relaciones para que hasRole() funcione correctamente
    $user->load('roles.permissions');
    $company           = Company::first() ?? Company::create(['name' => 'Empresa Test']);
    $branch            = Branch::first() ?? Branch::create(['name' => 'Sucursal Central', 'company_id' => $company->id]);
    $warehouseCategory = WarehouseCategory::first() ?? WarehouseCategory::create(['name' => 'General', 'description' => 'General']);
    $warehouse         = Warehouse::first() ?? Warehouse::create([
        'name'                 => 'Bodega Central',
        'branch_id'            => $branch->id,
        'id_branch'            => $branch->id,
        'warehouse_category_id' => $warehouseCategory->id,
    ]);
    $unit     = Unit::first() ?? Unit::create(['name' => 'Unidad', 'abbreviation' => 'UND']);
    $product  = Product::first() ?? Product::create([
        'name'    => 'Laptop 15"',
        'sku'     => 'LAP-15-TEST',
        'id_unit' => $unit->id,
    ]);
    $supplier = Supplier::first() ?? Supplier::create([
        'name'      => 'TechDistrib S.A.',
        'email'     => 'ventas@techdistrib.com',
        'country'   => 'Nicaragua',
        'is_active' => true,
    ]);
    $expenseType = ExpenseType::first() ?? ExpenseType::create([
        'name'        => 'Flete Terrestre',
        'description' => 'Costo de envío y transporte',
        'is_active'   => true,
    ]);

    return compact('user', 'branch', 'warehouse', 'unit', 'product', 'supplier', 'expenseType');
}

function postDataBase(array $data): array
{
    return [
        'id_supplier'           => $data['supplier']->id_supplier,
        'id_branch'             => $data['branch']->id,
        'id_warehouse'          => $data['warehouse']->id,
        'id_purchase_quotation' => null,
        'order_date'            => now()->format('Y-m-d'),
        'expected_date'         => now()->addDays(7)->format('Y-m-d'),
        'currency'              => 'USD',
        'payment_terms'         => 'Contado',
        'notes'                 => null,
        'products'              => [
            [
                'id_product' => $data['product']->id,
                'quantity'   => 5,
                'id_unit'    => $data['unit']->id,
                'unit_price' => 500.00,
                'discount'   => 0,
                'tax_rate'   => 15,
                'notes'      => null,
            ],
        ],
        'expenses' => [],
    ];
}

// =============================================================================
// TEST — PurchaseOrderService (unitario)
// =============================================================================

test('PurchaseOrderService calcula totales correctamente', function () {
    $service = new PurchaseOrderService();

    // 5 unidades x $500 = $2500 subtotal
    // descuento $0
    // base $2500, IVA 15% = $375
    // gastos adicionales $0
    // total = $2500 + $375 = $2875
    $totales = $service->calcularTotales(
        products: [
            ['quantity' => 5, 'unit_price' => 500, 'discount' => 0, 'tax_rate' => 15],
        ],
        expenses: []
    );

    expect($totales['subtotal'])->toBe(2500.0)
        ->and($totales['discount'])->toBe(0.0)
        ->and($totales['tax'])->toBe(375.0)
        ->and($totales['additional_expenses'])->toBe(0.0)
        ->and($totales['total'])->toBe(2875.0);
});

test('PurchaseOrderService calcula totales con descuento e IVA y gastos', function () {
    $service = new PurchaseOrderService();

    // 10 unidades x $200 = $2000 subtotal
    // descuento $100 -> base $1900, IVA 15% = $285
    // gasto $50
    // total = ($2000 - $100) + $285 + $50 = $2235
    $totales = $service->calcularTotales(
        products: [
            ['quantity' => 10, 'unit_price' => 200, 'discount' => 100, 'tax_rate' => 15],
        ],
        expenses: [
            ['amount' => 50],
        ]
    );

    expect($totales['subtotal'])->toBe(2000.0)
        ->and($totales['discount'])->toBe(100.0)
        ->and($totales['tax'])->toBe(285.0)
        ->and($totales['additional_expenses'])->toBe(50.0)
        ->and($totales['total'])->toBe(2235.0);
});

test('PurchaseOrderService lanza excepción al cambiar estado de orden cancelada', function () {
    $data  = setupDatosMaestros();
    $order = PurchaseOrder::create([
        'id_supplier'  => $data['supplier']->id_supplier,
        'id_branch'    => $data['branch']->id,
        'id_warehouse' => $data['warehouse']->id,
        'id_user'      => $data['user']->id,
        'order_date'   => now(),
        'expected_date' => now()->addDays(5),
        'currency'     => 'USD',
        'payment_terms' => 'Contado',
        'subtotal'     => 0,
        'discount'     => 0,
        'tax'          => 0,
        'additional_expenses' => 0,
        'total'        => 0,
        'status'       => 'cancelled',
    ]);

    $service = new PurchaseOrderService();

    expect(fn () => $service->cambiarEstado($order, 'issued'))
        ->toThrow(\InvalidArgumentException::class, 'Una orden cancelada no puede cambiar de estado.');
});

test('PurchaseOrderService lanza excepción al intentar regresar issued a draft', function () {
    $data  = setupDatosMaestros();
    $order = PurchaseOrder::create([
        'id_supplier'  => $data['supplier']->id_supplier,
        'id_branch'    => $data['branch']->id,
        'id_warehouse' => $data['warehouse']->id,
        'id_user'      => $data['user']->id,
        'order_date'   => now(),
        'expected_date' => now()->addDays(5),
        'currency'     => 'USD',
        'payment_terms' => 'Contado',
        'subtotal'     => 0,
        'discount'     => 0,
        'tax'          => 0,
        'additional_expenses' => 0,
        'total'        => 0,
        'status'       => 'issued',
    ]);

    $service = new PurchaseOrderService();

    expect(fn () => $service->cambiarEstado($order, 'draft'))
        ->toThrow(\InvalidArgumentException::class, 'Una orden emitida no puede regresar a borrador.');
});

// =============================================================================
// TEST — HTTP / Flujo completo (feature)
// =============================================================================

test('usuario autenticado puede crear una orden de compra con productos y gastos', function () {
    $data = setupDatosMaestros();

    $postData = array_merge(postDataBase($data), [
        'expenses' => [
            [
                'id_expense_type' => $data['expenseType']->id_expense_type,
                'description'     => 'Flete exprés',
                'amount'          => 75.00,
            ],
        ],
    ]);

    // 5 x $500 = $2500 - $0 = $2500, IVA 15% = $375, gasto $75 -> total $2950
    $response = $this->actingAs($data['user'])->post(route('purchase_orders.store'), $postData);

    $response->assertRedirect(route('purchase_orders.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('purchase_orders', [
        'id_supplier'        => $data['supplier']->id_supplier,
        'id_branch'          => $data['branch']->id,
        'subtotal'           => 2500.0000,
        'discount'           => 0.0000,
        'tax'                => 375.0000,
        'additional_expenses' => 75.0000,
        'total'              => 2950.0000,
        'currency'           => 'USD',
        'status'             => 'draft',
    ]);

    $order = PurchaseOrder::where('id_supplier', $data['supplier']->id_supplier)
        ->latest('id_purchase_order')
        ->first();

    $this->assertDatabaseHas('purchase_order_details', [
        'id_purchase_order' => $order->id_purchase_order,
        'id_product'        => $data['product']->id,
        'quantity'          => 5.0000,
        'unit_price'        => 500.0000,
        'subtotal'          => 2500.0000,
        'tax_rate'          => 15.0000,
        'tax_amount'        => 375.0000,
        'total'             => 2875.0000,
    ]);

    $this->assertDatabaseHas('purchase_order_expenses', [
        'id_purchase_order' => $order->id_purchase_order,
        'id_expense_type'   => $data['expenseType']->id_expense_type,
        'amount'            => 75.0000,
        'description'       => 'Flete exprés',
    ]);
});

test('no se puede crear una orden de compra sin productos', function () {
    $data = setupDatosMaestros();

    $postData           = postDataBase($data);
    $postData['products'] = [];

    $response = $this->actingAs($data['user'])->post(route('purchase_orders.store'), $postData);

    $response->assertSessionHasErrors('products');
});

test('no se puede crear una orden sin proveedor', function () {
    $data     = setupDatosMaestros();
    $postData = postDataBase($data);
    unset($postData['id_supplier']);

    $response = $this->actingAs($data['user'])->post(route('purchase_orders.store'), $postData);

    $response->assertSessionHasErrors('id_supplier');
});

test('usuario no autenticado no puede acceder al índice de órdenes de compra', function () {
    $response = $this->get(route('purchase_orders.index'));

    $response->assertRedirect(route('login'));
});

test('usuario autenticado puede cambiar estado de una orden a issued', function () {
    $data  = setupDatosMaestros();
    $order = PurchaseOrder::create([
        'id_supplier'         => $data['supplier']->id_supplier,
        'id_branch'           => $data['branch']->id,
        'id_warehouse'        => $data['warehouse']->id,
        'id_user'             => $data['user']->id,
        'order_date'          => now(),
        'expected_date'       => now()->addDays(5),
        'currency'            => 'USD',
        'payment_terms'       => 'Contado',
        'subtotal'            => 2500.00,
        'discount'            => 0,
        'tax'                 => 375.00,
        'additional_expenses' => 0,
        'total'               => 2875.00,
        'status'              => 'draft',
    ]);

    $response = $this->actingAs($data['user'])->patch(
        route('purchase_orders.updateStatus', $order),
        ['status' => 'issued']
    );

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('purchase_orders', [
        'id_purchase_order' => $order->id_purchase_order,
        'status'            => 'issued',
    ]);
});

test('no se puede cambiar estado de una orden cancelada', function () {
    $data  = setupDatosMaestros();
    $order = PurchaseOrder::create([
        'id_supplier'         => $data['supplier']->id_supplier,
        'id_branch'           => $data['branch']->id,
        'id_warehouse'        => $data['warehouse']->id,
        'id_user'             => $data['user']->id,
        'order_date'          => now(),
        'expected_date'       => now()->addDays(5),
        'currency'            => 'USD',
        'payment_terms'       => 'Contado',
        'subtotal'            => 0,
        'discount'            => 0,
        'tax'                 => 0,
        'additional_expenses' => 0,
        'total'               => 0,
        'status'              => 'cancelled',
    ]);

    $response = $this->actingAs($data['user'])->patch(
        route('purchase_orders.updateStatus', $order),
        ['status' => 'issued']
    );

    $response->assertSessionHas('error');

    // El estado no debe haber cambiado
    $this->assertDatabaseHas('purchase_orders', [
        'id_purchase_order' => $order->id_purchase_order,
        'status'            => 'cancelled',
    ]);
});

test('se puede eliminar una orden en borrador', function () {
    $data  = setupDatosMaestros();
    $order = PurchaseOrder::create([
        'id_supplier'         => $data['supplier']->id_supplier,
        'id_branch'           => $data['branch']->id,
        'id_warehouse'        => $data['warehouse']->id,
        'id_user'             => $data['user']->id,
        'order_date'          => now(),
        'expected_date'       => now()->addDays(5),
        'currency'            => 'USD',
        'payment_terms'       => 'Contado',
        'subtotal'            => 0,
        'discount'            => 0,
        'tax'                 => 0,
        'additional_expenses' => 0,
        'total'               => 0,
        'status'              => 'draft',
    ]);

    $response = $this->actingAs($data['user'])
        ->delete(route('purchase_orders.destroy', $order));

    $response->assertRedirect(route('purchase_orders.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('purchase_orders', [
        'id_purchase_order' => $order->id_purchase_order,
    ]);
});

test('no se puede eliminar una orden que ya fue emitida', function () {
    $data  = setupDatosMaestros();
    $order = PurchaseOrder::create([
        'id_supplier'         => $data['supplier']->id_supplier,
        'id_branch'           => $data['branch']->id,
        'id_warehouse'        => $data['warehouse']->id,
        'id_user'             => $data['user']->id,
        'order_date'          => now(),
        'expected_date'       => now()->addDays(5),
        'currency'            => 'USD',
        'payment_terms'       => 'Contado',
        'subtotal'            => 0,
        'discount'            => 0,
        'tax'                 => 0,
        'additional_expenses' => 0,
        'total'               => 0,
        'status'              => 'issued',
    ]);

    $response = $this->actingAs($data['user'])
        ->delete(route('purchase_orders.destroy', $order));

    $response->assertSessionHas('error');

    // La orden debe seguir en la BD
    $this->assertDatabaseHas('purchase_orders', [
        'id_purchase_order' => $order->id_purchase_order,
    ]);
});

test('se genera correctamente el código de orden con formato OC-YYYY-NNNN', function () {
    $data = setupDatosMaestros();

    $response = $this->actingAs($data['user'])->post(route('purchase_orders.store'), postDataBase($data));

    $response->assertRedirect(route('purchase_orders.index'));

    $order = PurchaseOrder::where('id_supplier', $data['supplier']->id_supplier)
        ->latest('id_purchase_order')
        ->first();

    expect($order->purchase_order_code)->toMatch('/^OC-\d{4}-\d{4}$/');
});
