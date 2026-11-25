<?php

use App\Models\Branch;
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

    (new PriceService)->cambiarPrecio($product, 20);

    $customer = Customer::create(['name' => "Cliente {$tag}", 'customer_type' => 'final', 'email' => 'cliente@example.com']);

    return (object) [
        'company' => $company, 'sucursal' => $sucursal, 'bodega' => $bodega, 'a1' => $a1,
        'product' => $product, 'l1' => $l1, 'l2' => $l2, 'customer' => $customer,
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
        'lines' => [['id_product' => $e->product->id_product, 'quantity' => 7]],
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
        ->post(route('sales.store'), ventaPayload($e, ['lines' => [['id_product' => $e->product->id_product, 'quantity' => 20]]]))
        ->assertSessionHasErrors('lines');

    expect(Sale::queryAllBranches()->count())->toBe(0);
});

it('no vende un producto sin precio asignado', function () {
    Mail::fake();
    $e = ventaEscenario();
    $otro = Product::create(['name' => 'Sin precio', 'sku' => 'SP-'.Str::upper(Str::random(5))]);

    $this->actingAs($e->vendedor)
        ->post(route('sales.store'), ventaPayload($e, ['lines' => [['id_product' => $otro->id_product, 'quantity' => 1]]]))
        ->assertSessionHasErrors();

    expect(Sale::queryAllBranches()->count())->toBe(0);
});

it('envía el comprobante al correo del cliente al finalizar la venta', function () {
    Mail::fake();
    $e = ventaEscenario();

    $this->actingAs($e->vendedor)->post(route('sales.store'), ventaPayload($e))->assertRedirect();

    Mail::assertQueued(SaleReceiptMail::class);
    expect(Sale::queryAllBranches()->latest('id_sale')->first()->emailed_at)->not->toBeNull();
});

it('cambiar el precio cierra el vigente y abre uno nuevo (historial)', function () {
    $tag = Str::upper(Str::random(5));
    $product = Product::create(['name' => "Prod {$tag}", 'sku' => 'PH-'.$tag]);

    $svc = new PriceService;
    $svc->cambiarPrecio($product, 10, 'inicial');
    $svc->cambiarPrecio($product, 12.5, 'ajuste');

    $historial = $product->prices()->orderBy('valid_from')->get();
    expect($historial)->toHaveCount(2);
    expect($historial->whereNull('valid_until')->count())->toBe(1);
    expect((float) $product->precioVigente())->toBe(12.5);
    expect($historial->firstWhere('valid_until', '!=', null))->not->toBeNull(); // el anterior quedó cerrado
});
