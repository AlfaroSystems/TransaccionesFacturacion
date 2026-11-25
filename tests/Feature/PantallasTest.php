<?php

use App\Models\Branch;
use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Department;
use App\Models\District;
use App\Models\Employee;
use App\Models\InventoryLot;
use App\Models\InventoryTransfer;
use App\Models\Location;
use App\Models\Municipality;
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
use App\Models\Vehicle;
use App\Models\Warehouse;
use App\Models\WarehouseCategory;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Crea un registro de cada modelo con pantalla propia y devuelve el valor de cada
 * parámetro de ruta.
 */
function registrosParaPantallas(User $admin): array
{
    // Ubicación geográfica completa, para que las pantallas carguen esas relaciones
    $department = Department::create(['code' => 'DP', 'name' => 'Departamento', 'short_name' => 'DP']);
    $municipality = Municipality::create(['id_department' => $department->id_department, 'code' => 'MU', 'name' => 'Municipio']);
    $district = District::create(['id_municipality' => $municipality->id_municipality, 'code' => 'DI', 'name' => 'Distrito']);
    $geo = [
        'id_department' => $department->id_department,
        'id_municipality' => $municipality->id_municipality,
        'id_district' => $district->id_district,
    ];

    $company = Company::create(['name' => 'Empresa Pantallas', 'address' => 'Dirección', ...$geo]);
    $branch = Branch::create(['id_company' => $company->id_company, 'name' => 'Sucursal Pantallas', 'address' => 'Dirección', ...$geo]);
    $whCat = WarehouseCategory::create(['name' => 'General']);
    $warehouse = Warehouse::create(['id_branch' => $branch->id_branch, 'id_warehouse_category' => $whCat->id_warehouse_category, 'name' => 'Bodega']);
    $location = Location::create(['id_warehouse' => $warehouse->id_warehouse, 'code' => 'LOC-'.Str::random(6), 'aisle' => 'A', 'capacity' => 1]);
    $category = Category::create(['name' => 'Categoría']);
    $sub = SubCategory::create(['id_category' => $category->id_category, 'name' => 'Subcategoría']);
    $unit = Unit::create(['name' => 'Unidad', 'abbreviation' => 'UND']);
    $product = Product::create([
        'name' => 'Producto', 'sku' => 'SKU-'.Str::random(6), 'id_category' => $category->id_category,
        'id_sub_category' => $sub->id_sub_category, 'id_purchase_unit' => $unit->id_unit, 'id_sale_unit' => $unit->id_unit,
    ]);
    $supplier = Supplier::create(['name' => 'Proveedor', 'email' => 'p@example.com', 'country' => 'El Salvador', 'is_active' => true, ...$geo]);
    $supplier->contacts()->create(['full_name' => 'Contacto', 'phone' => '2222-2222', 'email' => 'c@example.com', 'is_active' => true]);
    $role = Role::create(['name' => 'rol-pantallas']);

    $request = PurchaseRequest::create([
        'uuid' => (string) Str::uuid(), 'purchase_request_code' => 'REQ-PANTALLAS', 'id_branch' => $branch->id_branch,
        'id_warehouse' => $warehouse->id_warehouse, 'id_user' => $admin->id_user, 'request_date' => now(),
        'required_date' => now(), 'justification' => 'x', 'status' => 'draft',
    ]);
    $request->details()->create(['id_product' => $product->id_product, 'quantity' => 1, 'id_unit' => $unit->id_unit]);
    $quotationRequest = PurchaseQuotationRequest::createFromPurchaseRequests(collect([$request]));
    $order = PurchaseOrder::create([
        'id_supplier' => $supplier->id_supplier, 'id_branch' => $branch->id_branch, 'id_warehouse' => $warehouse->id_warehouse,
        'order_date' => now(), 'status' => 'draft',
    ]);
    $purchase = Purchase::create([
        'id_purchase_order' => $order->id_purchase_order, 'id_supplier' => $supplier->id_supplier,
        'id_branch' => $branch->id_branch, 'purchase_date' => now(), 'status' => 'draft',
    ]);
    $retaceo = Retaceo::create([
        'id_supplier' => $supplier->id_supplier, 'id_purchase' => $purchase->id_purchase, 'retaceo_date' => now(), 'status' => 'draft',
    ]);
    $employee = Employee::create(['first_name' => 'Juan', 'last_name' => 'Pérez', 'dui' => '01234567-8', 'position' => 'Motorista', 'id_branch' => $branch->id_branch]);
    $vehicle = Vehicle::create(['brand' => 'Isuzu', 'model' => 'NPR', 'year' => 2020, 'license_plate' => 'C123-456', 'id_responsible_employee' => $employee->id_employee]);
    $vehicle->locations()->create(['id_warehouse' => $warehouse->id_warehouse, 'code' => 'CAJON-1', 'capacity' => 50]);
    $bodegaDestino = Warehouse::create(['id_branch' => $branch->id_branch, 'id_warehouse_category' => $whCat->id_warehouse_category, 'name' => 'Bodega destino']);
    $transfer = InventoryTransfer::create([
        'id_source_warehouse' => $warehouse->id_warehouse, 'id_destination_warehouse' => $bodegaDestino->id_warehouse, 'transfer_date' => now(),
        'id_vehicle' => $vehicle->id_vehicle, 'id_driver_employee' => $employee->id_employee,
    ]);
    $lot = InventoryLot::create([
        'lot_code' => 'LOT-PANTALLAS', 'id_product' => $product->id_product, 'id_warehouse' => $warehouse->id_warehouse, 'received_at' => now(),
        'quantity_received' => 5, 'unit_cost' => 10, 'total_cost' => 50, 'status' => InventoryLot::STATUS_PENDING,
    ]);
    $customer = Customer::create(['name' => 'Cliente Pantallas', 'customer_type' => 'final', 'email' => 'cliente@example.com']);
    $sale = Sale::create([
        'id_branch' => $branch->id_branch, 'id_customer' => $customer->id_customer, 'id_user' => $admin->id_user,
        'sale_date' => now(), 'document_type' => 'invoice', 'payment_method' => 'cash', 'status' => Sale::STATUS_DELIVERED,
    ]);
    $sale->details()->create([
        'id_product' => $product->id_product, 'quantity' => 1, 'id_unit' => $unit->id_unit, 'unit_price' => 10,
        'subtotal' => 10, 'tax_amount' => 1.3, 'total' => 11.3,
    ]);

    return [
        'branch' => $branch->id_branch, 'category' => $category->id_category, 'company' => $company->id_company, 'location' => $location->id_location,
        'product' => $product->id_product, 'purchase_quotation_request' => $quotationRequest->id_purchase_quotation_request,
        'purchase_request' => $request->id_purchase_request, 'purchaseRequest' => $request->id_purchase_request,
        'purchase_order' => $order->id_purchase_order, 'purchase' => $purchase->id_purchase, 'retaceo' => $retaceo->id_retaceo,
        'transfer' => $transfer->id_inventory_transfer, 'lot' => $lot->id_inventory_lot,
        'employee' => $employee->id_employee, 'vehicle' => $vehicle->id_vehicle, 'role' => $role->id_role, 'subcategory' => $sub->id_sub_category, 'supplier' => $supplier->id_supplier, 'unit' => $unit->id_unit,
        'user' => $admin->id_user, 'warehouse_category' => $whCat->id_warehouse_category, 'warehouse' => $warehouse->id_warehouse,
        'sale' => $sale->id_sale, 'customer' => $customer->id_customer,
    ];
}

test('las búsquedas no distinguen mayúsculas de minúsculas', function () {
    $admin = User::factory()->create(['username' => 'Administradora General']);
    $admin->roles()->attach(Role::firstOrCreate(['name' => 'admin'])->id_role, ['assigned_at' => now()]);
    $product = Product::create(['name' => 'Laptop Dell Latitude', 'sku' => 'SKU-'.Str::random(6)]);
    $supplier = Supplier::create(['name' => 'Distribuidora ACME', 'email' => 'acme@example.com', 'country' => 'El Salvador', 'is_active' => true]);
    $company = Company::create(['name' => 'Comercial Hernández']);

    $this->actingAs($admin);

    $ids = fn ($items) => collect($items instanceof Paginator ? $items->items() : $items)->map->getKey();

    expect($ids($this->get(route('products.index', ['search' => 'LAPTOP dell']))->viewData('products')))->toContain($product->id_product)
        ->and($ids($this->get(route('suppliers.index', ['search' => 'acme']))->viewData('suppliers')))->toContain($supplier->id_supplier)
        ->and($ids($this->get(route('companies.index', ['search' => 'COMERCIAL']))->viewData('companies')))->toContain($company->id_company)
        ->and($ids($this->get(route('users.index', ['search' => 'administradora']))->viewData('users')))->toContain($admin->id_user);
});

test('ninguna pantalla de listado, crear, editar o ver responde con error', function () {
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::firstOrCreate(['name' => 'admin'])->id_role, ['assigned_at' => now()]);
    $params = registrosParaPantallas($admin);

    // Sin la página de error de depuración, que tarda varios segundos por cada error
    config(['app.debug' => false]);

    $this->actingAs($admin);
    $errores = [];

    foreach (Route::getRoutes() as $route) {
        $name = (string) $route->getName();

        if (! in_array('GET', $route->methods()) || ! preg_match('/\.(index|create|edit|show)$/', $name)) {
            continue;
        }

        $url = route($name, array_intersect_key($params, array_flip($route->parameterNames())));

        // Cada pantalla en su propio savepoint: un error de SQL no arrastra a las siguientes
        DB::beginTransaction();
        $response = $this->get($url);
        DB::rollBack();

        if ($response->getStatusCode() >= 500) {
            $errores[] = "{$name} ({$url}) respondió {$response->getStatusCode()}: "
                .Str::limit((string) $response->exception?->getMessage(), 160);
        }
    }

    expect($errores)->toBe([]);
});

test('los filtros y las URL con valores inválidos no responden con error', function () {
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::firstOrCreate(['name' => 'admin'])->id_role, ['assigned_at' => now()]);
    config(['app.debug' => false]);
    $this->actingAs($admin);

    // Fechas y ids escritos a mano en la URL: antes llegaban a PostgreSQL y respondía con un 500
    $filtros = [
        'date_from' => 'ayer', 'date_to' => '2026-13-45', 'id_user' => 'abc', 'id_supplier' => 'abc',
        'id_category' => 'x', 'id_branch' => 'x', 'id_warehouse' => 'x', 'id_purchase_order' => 'x', 'id_purchase' => 'x',
    ];
    foreach (['audit-logs.index', 'purchases.index', 'purchases.create', 'retaceos.index', 'retaceos.create',
        'inventory.movements', 'products.index', 'subcategories.index', 'users.index'] as $ruta) {
        $this->get(route($ruta, $filtros))->assertOk();
    }

    foreach (['/purchases/abc', '/purchase_orders/abc/pdf', '/purchases/order-data/abc', '/retaceos/purchase-data/abc', '/users/abc/edit'] as $url) {
        $this->get($url)->assertNotFound();
    }
});

test('editar una empresa sin enviar los campos opcionales no falla', function () {
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::firstOrCreate(['name' => 'admin'])->id_role, ['assigned_at' => now()]);
    $company = Company::create(['name' => 'Empresa', 'nit' => '0614-010101-101-1']);

    $this->actingAs($admin)
        ->put(route('companies.update', $company), ['name' => 'Empresa Renombrada'])
        ->assertRedirect(route('companies.index'));

    expect($company->fresh()->name)->toBe('Empresa Renombrada')
        ->and($company->fresh()->nit)->toBeNull();
});

test('las pantallas cargan sus librerías desde el build de Vite y no desde un CDN', function () {
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::firstOrCreate(['name' => 'admin'])->id_role, ['assigned_at' => now()]);
    $this->actingAs($admin);

    // Sin versión fija ni verificación de integridad, el CDN podía servir cualquier script
    $this->get(route('dashboard'))->assertOk()
        ->assertDontSee('cdn.jsdelivr.net', false)
        ->assertSee('/build/assets/dashboard-', false);

    $this->get(route('purchases.index'))->assertOk()->assertDontSee('cdn.jsdelivr.net', false);
});
