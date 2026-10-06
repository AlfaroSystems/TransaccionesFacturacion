<?php

use App\Models\Branch;
use App\Models\Category;
use App\Models\Company;
use App\Models\Location;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\PurchaseQuotationRequest;
use App\Models\PurchaseRequest;
use App\Models\Retaceo;
use App\Models\Role;
use App\Models\SubCategory;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseCategory;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Crea un registro de cada modelo con pantalla propia y devuelve el valor de cada
 * parámetro de ruta.
 */
function registrosParaPantallas(User $admin): array
{
    $company   = Company::create(['name' => 'Empresa Pantallas']);
    $branch    = Branch::create(['company_id' => $company->id, 'name' => 'Sucursal Pantallas']);
    $whCat     = WarehouseCategory::create(['name' => 'General']);
    $warehouse = Warehouse::create(['branch_id' => $branch->id, 'warehouse_category_id' => $whCat->id, 'name' => 'Bodega']);
    $location  = Location::create(['warehouse_id' => $warehouse->id, 'code' => 'LOC-'.Str::random(6), 'capacity' => 1]);
    $category  = Category::create(['name' => 'Categoría']);
    $sub       = SubCategory::create(['id_category' => $category->id, 'name' => 'Subcategoría']);
    $unit      = Unit::create(['name' => 'Unidad', 'abbreviation' => 'UND']);
    $product   = Product::create(['name' => 'Producto', 'sku' => 'SKU-'.Str::random(6)]);
    $supplier  = Supplier::create(['name' => 'Proveedor', 'email' => 'p@example.com', 'country' => 'El Salvador', 'is_active' => true]);
    $role      = Role::create(['name' => 'rol-pantallas']);

    $request = PurchaseRequest::create([
        'uuid' => (string) Str::uuid(), 'purchase_request_code' => 'REQ-PANTALLAS', 'id_branch' => $branch->id,
        'id_warehouse' => $warehouse->id, 'id_user' => $admin->id, 'request_date' => now(),
        'required_date' => now(), 'justification' => 'x', 'status' => 'draft',
    ]);
    $quotationRequest = PurchaseQuotationRequest::create(['id_purchase_request' => $request->id_purchase_request]);
    $order = PurchaseOrder::create([
        'id_supplier' => $supplier->id_supplier, 'id_branch' => $branch->id, 'id_warehouse' => $warehouse->id,
        'order_date' => now(), 'status' => 'draft',
    ]);
    $purchase = Purchase::create([
        'id_purchase_order' => $order->id_purchase_order, 'id_supplier' => $supplier->id_supplier,
        'id_branch' => $branch->id, 'purchase_date' => now(), 'status' => 'draft',
    ]);
    $retaceo = Retaceo::create([
        'id_supplier' => $supplier->id_supplier, 'id_purchase' => $purchase->id_purchase, 'retaceo_date' => now(), 'status' => 'draft',
    ]);

    return [
        'branch' => $branch->id, 'category' => $category->id, 'company' => $company->id, 'location' => $location->id,
        'product' => $product->id, 'purchase_quotation_request' => $quotationRequest->id_purchase_quotation_request,
        'purchase_request' => $request->id_purchase_request, 'purchaseRequest' => $request->id_purchase_request,
        'purchase_order' => $order->id_purchase_order, 'purchase' => $purchase->id_purchase, 'retaceo' => $retaceo->id_retaceo,
        'role' => $role->id, 'subcategory' => $sub->id, 'supplier' => $supplier->id_supplier, 'unit' => $unit->id,
        'user' => $admin->id, 'warehouse_category' => $whCat->id, 'warehouse' => $warehouse->id,
    ];
}

test('las búsquedas no distinguen mayúsculas de minúsculas', function () {
    $admin = User::factory()->create(['name' => 'Administradora General']);
    $admin->roles()->attach(Role::firstOrCreate(['name' => 'admin'])->id, ['assigned_at' => now()]);
    $product = Product::create(['name' => 'Laptop Dell Latitude', 'sku' => 'SKU-'.Str::random(6)]);
    $supplier = Supplier::create(['name' => 'Distribuidora ACME', 'email' => 'acme@example.com', 'country' => 'El Salvador', 'is_active' => true]);
    $company = Company::create(['name' => 'Comercial Hernández']);

    $this->actingAs($admin);

    $ids = fn ($items) => collect($items instanceof \Illuminate\Contracts\Pagination\Paginator ? $items->items() : $items)->map->getKey();

    expect($ids($this->get(route('products.index', ['search' => 'LAPTOP dell']))->viewData('products')))->toContain($product->id)
        ->and($ids($this->get(route('suppliers.index', ['search' => 'acme']))->viewData('suppliers')))->toContain($supplier->id_supplier)
        ->and($ids($this->get(route('companies.index', ['search' => 'COMERCIAL']))->viewData('companies')))->toContain($company->id)
        ->and($ids($this->get(route('users.index', ['search' => 'administradora']))->viewData('users')))->toContain($admin->id);
});

test('ninguna pantalla de crear, editar o ver responde con error', function () {
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::firstOrCreate(['name' => 'admin'])->id, ['assigned_at' => now()]);
    $params = registrosParaPantallas($admin);

    // Sin la página de error de depuración, que tarda varios segundos por cada error
    config(['app.debug' => false]);

    $this->actingAs($admin);
    $errores = [];

    foreach (Route::getRoutes() as $route) {
        $name = (string) $route->getName();

        if (! in_array('GET', $route->methods()) || ! preg_match('/\.(create|edit|show)$/', $name)) {
            continue;
        }

        $url = route($name, array_intersect_key($params, array_flip($route->parameterNames())));
        $status = $this->get($url)->getStatusCode();

        if ($status >= 500) {
            $errores[] = "{$name} ({$url}) respondió {$status}";
        }
    }

    expect($errores)->toBe([]);
});
