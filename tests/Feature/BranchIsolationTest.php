<?php

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Location;
use App\Models\Permission;
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
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

// =============================================================================
// Escenario: dos empresas, cada una con una sucursal y la cadena completa de
// documentos (solicitud -> cotización -> orden -> compra -> retaceo).
// =============================================================================

const PERMISOS_AISLAMIENTO = [
    'purchase_requests.ver', 'purchase_requests.crear', 'purchase_requests.editar', 'purchase_requests.eliminar', 'purchase_requests.aprobar',
    'purchase_quotation_requests.ver', 'purchase_quotation_requests.crear', 'purchase_quotation_requests.seleccionar_cotizacion',
    'purchase_quotations.crear', 'purchase_quotations.eliminar',
    'purchase_orders.ver', 'purchase_orders.crear', 'purchase_orders.editar', 'purchase_orders.eliminar', 'purchase_orders.aprobar', 'purchase_orders.pdf',
    'purchases.ver', 'purchases.crear', 'purchases.editar', 'purchases.eliminar', 'purchases.cambiar_estado',
    'retaceos.ver', 'retaceos.crear', 'retaceos.editar', 'retaceos.eliminar', 'retaceos.cambiar_estado',
    'warehouses.ver', 'warehouses.crear', 'warehouses.editar', 'warehouses.eliminar',
    'locations.ver', 'locations.crear', 'locations.editar', 'locations.eliminar',
    'branches.ver', 'branches.crear', 'branches.editar', 'branches.eliminar',
    'companies.ver', 'companies.crear', 'companies.editar', 'companies.eliminar',
    'usuarios.ver', 'usuarios.crear', 'usuarios.editar', 'usuarios.eliminar',
    'bitacora.ver',
];

function usuarioDeSucursal(?int $branchId): User
{
    $role = Role::create(['name' => 'operador-'.Str::lower(Str::random(8))]);

    foreach (PERMISOS_AISLAMIENTO as $permiso) {
        Permission::firstOrCreate(['id' => $permiso], ['name' => $permiso]);

        if (! Gate::has($permiso)) {
            Gate::define($permiso, fn (User $user) => $user->hasPermission($permiso));
        }
    }

    $role->permissions()->sync(PERMISOS_AISLAMIENTO);

    $user = User::factory()->create(['id_branch' => $branchId]);
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    return $user->load('roles.permissions');
}

function administradorGlobal(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::firstOrCreate(['name' => 'admin'])->id, ['assigned_at' => now()]);

    return $user->load('roles');
}

function documentosDeSucursal(WarehouseCategory $category, Unit $unit, Product $product, Supplier $supplier): object
{
    $tag = Str::upper(Str::random(6));

    $company   = Company::create(['name' => "Empresa {$tag}"]);
    $branch    = Branch::create(['company_id' => $company->id, 'name' => "Sucursal {$tag}"]);
    $warehouse = Warehouse::create(['branch_id' => $branch->id, 'warehouse_category_id' => $category->id, 'name' => "Bodega {$tag}"]);
    $location  = Location::create(['warehouse_id' => $warehouse->id, 'code' => "LOC-{$tag}", 'capacity' => 10]);
    $user      = usuarioDeSucursal($branch->id);

    $request = PurchaseRequest::create([
        'uuid'                  => (string) Str::uuid(),
        'purchase_request_code' => "REQ-TEST-{$tag}",
        'id_branch'             => $branch->id,
        'id_warehouse'          => $warehouse->id,
        'id_user'               => $user->id,
        'request_date'          => now(),
        'required_date'         => now()->addWeek(),
        'justification'         => "Justificación {$tag}",
        'status'                => 'approved',
    ]);
    $requestDetail = $request->details()->create(['id_product' => $product->id, 'quantity' => 5, 'id_unit' => $unit->id]);

    $quotationRequest = PurchaseQuotationRequest::create(['id_purchase_request' => $request->id_purchase_request]);
    $quotation = PurchaseQuotation::create([
        'id_purchase_quotation_request' => $quotationRequest->id_purchase_quotation_request,
        'id_supplier'                   => $supplier->id_supplier,
        'quotation_date'                => now(),
        'status'                        => 'approved',
    ]);

    $order = PurchaseOrder::create([
        'id_supplier'           => $supplier->id_supplier,
        'id_branch'             => $branch->id,
        'id_warehouse'          => $warehouse->id,
        'id_purchase_quotation' => $quotation->id_purchase_quotation,
        'id_user'               => $user->id,
        'order_date'            => now(),
        'expected_date'         => now()->addWeek(),
        'currency'              => 'USD',
        'payment_terms'         => 'Contado',
        'status'                => 'issued',
    ]);
    $orderDetail = PurchaseOrderDetail::create([
        'id_purchase_order' => $order->id_purchase_order,
        'id_product'        => $product->id,
        'quantity'          => 5,
        'id_unit'           => $unit->id,
        'unit_price'        => 10,
    ]);

    $purchase = Purchase::create([
        'id_purchase_order' => $order->id_purchase_order,
        'id_supplier'       => $supplier->id_supplier,
        'id_branch'         => $branch->id,
        'id_warehouse'      => $warehouse->id,
        'purchase_date'     => now(),
        'status'            => 'draft',
    ]);
    $purchaseDetail = PurchaseDetail::create([
        'id_purchase'              => $purchase->id_purchase,
        'id_purchase_order_detail' => $orderDetail->id_purchase_order_detail,
        'id_product'               => $product->id,
        'quantity_received'        => 1,
        'unit_price'               => 10,
    ]);

    $retaceo = Retaceo::create([
        'id_supplier'  => $supplier->id_supplier,
        'id_purchase'  => $purchase->id_purchase,
        'retaceo_date' => now(),
        'status'       => 'draft',
    ]);

    return (object) compact(
        'company', 'branch', 'warehouse', 'location', 'user', 'request', 'requestDetail',
        'quotationRequest', 'quotation', 'order', 'orderDetail',
        'purchase', 'purchaseDetail', 'retaceo'
    );
}

function escenarioDosSucursales(): array
{
    $tag      = Str::upper(Str::random(6));
    $category = WarehouseCategory::create(['name' => "Categoría {$tag}"]);
    $unit     = Unit::create(['name' => "Unidad {$tag}", 'abbreviation' => 'UND']);
    $product  = Product::create(['name' => "Producto {$tag}", 'sku' => "SKU-{$tag}"]);
    $supplier = Supplier::create(['name' => "Proveedor {$tag}", 'email' => "p{$tag}@example.com", 'country' => 'El Salvador', 'is_active' => true]);

    return [
        documentosDeSucursal($category, $unit, $product, $supplier),
        documentosDeSucursal($category, $unit, $product, $supplier),
        compact('unit', 'product', 'supplier'),
    ];
}

/**
 * Compra recibida y sin retaceo de la sucursal: la única que admite un retaceo nuevo.
 */
function compraRecibidaSinRetaceo(object $sucursal): Purchase
{
    return Purchase::queryAllBranches()->create([
        'id_purchase_order' => $sucursal->order->id_purchase_order,
        'id_supplier'       => $sucursal->order->id_supplier,
        'id_branch'         => $sucursal->branch->id,
        'id_warehouse'      => $sucursal->warehouse->id,
        'purchase_date'     => now(),
        'status'            => 'received',
    ]);
}

/**
 * Verifica que una colección de la vista contiene el registro propio y no el ajeno.
 */
function expectSoloPropio($items, $propio, $ajeno): void
{
    $ids = collect($items instanceof \Illuminate\Contracts\Pagination\Paginator ? $items->items() : $items)
        ->map(fn ($item) => $item->getKey());

    expect($ids)->toContain($propio->getKey())
        ->and($ids)->not->toContain($ajeno->getKey());
}

// =============================================================================
// Listados, desplegables y métricas
// =============================================================================

test('los listados y desplegables solo muestran datos de la sucursal del usuario', function () {
    [$a, $b] = escenarioDosSucursales();
    $this->actingAs($a->user);

    $r = $this->get(route('purchase-requests.index'))->assertOk();
    expectSoloPropio($r->viewData('purchaseRequests'), $a->request, $b->request);
    expectSoloPropio($r->viewData('branches'), $a->branch, $b->branch);
    expectSoloPropio($r->viewData('warehouses'), $a->warehouse, $b->warehouse);

    $r = $this->get(route('purchase-quotation-requests.index'))->assertOk();
    expectSoloPropio($r->viewData('quotationRequests'), $a->quotationRequest, $b->quotationRequest);

    $ids = collect($this->getJson(route('purchase-quotation-requests.approved-requests'))->assertOk()->json())
        ->pluck('id_purchase_request');
    expect($ids)->toContain($a->request->id_purchase_request)->not->toContain($b->request->id_purchase_request);

    $r = $this->get(route('purchase_orders.index'))->assertOk();
    expectSoloPropio($r->viewData('purchase_orders'), $a->order, $b->order);
    expectSoloPropio($r->viewData('branches'), $a->branch, $b->branch);
    expectSoloPropio($r->viewData('warehouses'), $a->warehouse, $b->warehouse);

    $r = $this->get(route('purchases.index'))->assertOk();
    expectSoloPropio($r->viewData('purchases'), $a->purchase, $b->purchase);
    expectSoloPropio($r->viewData('orders'), $a->order, $b->order);
    expect($r->viewData('totalCount'))->toBe(1);

    // Para un retaceo nuevo solo se ofrecen compras recibidas y sin retaceo activo
    [$recibidaA, $recibidaB] = [compraRecibidaSinRetaceo($a), compraRecibidaSinRetaceo($b)];

    $r = $this->get(route('retaceos.index'))->assertOk();
    expectSoloPropio($r->viewData('retaceos'), $a->retaceo, $b->retaceo);
    expectSoloPropio($r->viewData('purchasesForModal'), $recibidaA, $recibidaB);
    expect($r->viewData('totalCount'))->toBe(1);

    $r = $this->get(route('warehouses.index'))->assertOk();
    expectSoloPropio($r->viewData('warehouses'), $a->warehouse, $b->warehouse);

    $r = $this->get(route('locations.index'))->assertOk();
    expectSoloPropio($r->viewData('locations'), $a->location, $b->location);

    $r = $this->get(route('branches.index'))->assertOk();
    expectSoloPropio($r->viewData('branches'), $a->branch, $b->branch);
    expectSoloPropio($r->viewData('companies'), $a->company, $b->company);

    $r = $this->get(route('companies.index'))->assertOk();
    expectSoloPropio($r->viewData('companies'), $a->company, $b->company);

    $r = $this->get(route('users.index'))->assertOk();
    expectSoloPropio($r->viewData('users'), $a->user, $b->user);

    $r = $this->get(route('dashboard'))->assertOk();
    expect($r->viewData('purchaseOrderCount'))->toBe(1)
        ->and($r->viewData('purchaseRequestCount'))->toBe(1)
        ->and($r->viewData('purchaseCount'))->toBe(2) // la del escenario y la recibida de este test
        ->and($r->viewData('retaceoCount'))->toBe(1)
        ->and($r->viewData('userCount'))->toBe(1);
});

test('la bitácora solo muestra la actividad de usuarios de la misma sucursal', function () {
    [$a, $b] = escenarioDosSucursales();
    $propio = AuditLog::create(['user_id' => $a->user->id, 'controller' => 'Prueba', 'action' => 'propio']);
    $ajeno  = AuditLog::create(['user_id' => $b->user->id, 'controller' => 'Prueba', 'action' => 'ajeno']);

    $r = $this->actingAs($a->user)->get(route('audit-logs.index', ['controller' => 'Prueba']))->assertOk();

    expectSoloPropio($r->viewData('logs'), $propio, $ajeno);
    expectSoloPropio($r->viewData('users'), $a->user, $b->user);
});

// =============================================================================
// Acceso directo por ID a registros de otra sucursal
// =============================================================================

test('los registros de otra sucursal no se pueden ver, editar ni borrar por ID', function () {
    [$a, $b] = escenarioDosSucursales();
    $this->actingAs($a->user);

    $rutas = [
        ['get', route('purchase-requests.show', $b->request)],
        ['get', route('purchase-requests.edit', $b->request)],
        ['put', route('purchase-requests.update', $b->request)],
        ['delete', route('purchase-requests.destroy', $b->request)],
        ['patch', route('purchase-requests.update-status', $b->request)],

        ['get', route('purchase-quotation-requests.show', $b->quotationRequest->id_purchase_quotation_request)],
        ['get', route('purchase-quotation-requests.request-details', $b->request->id_purchase_request)],
        ['patch', route('purchase-quotation-requests.select-quotation', [$b->quotationRequest, $b->quotation])],
        ['delete', route('purchase-quotations.destroy', $b->quotation->id_purchase_quotation)],

        ['get', route('purchase_orders.show', $b->order)],
        ['get', route('purchase_orders.edit', $b->order)],
        ['get', route('purchase_orders.pdf', $b->order)],
        ['get', route('purchase_orders.quotation-data', $b->quotation->id_purchase_quotation)],
        ['put', route('purchase_orders.update', $b->order)],
        ['patch', route('purchase_orders.updateStatus', $b->order)],
        ['delete', route('purchase_orders.destroy', $b->order)],

        ['get', route('purchases.show', $b->purchase)],
        ['get', route('purchases.edit', $b->purchase)],
        ['get', route('purchases.pdf', $b->purchase)],
        ['get', route('purchases.edit-data', $b->purchase)],
        ['get', route('purchases.order-data', $b->order->id_purchase_order)],
        ['put', route('purchases.update', $b->purchase)],
        ['patch', route('purchases.updateStatus', $b->purchase)],
        ['delete', route('purchases.destroy', $b->purchase)],

        ['get', route('retaceos.show', $b->retaceo)],
        ['get', route('retaceos.edit', $b->retaceo)],
        ['get', route('retaceos.pdf', $b->retaceo)],
        ['get', route('retaceos.edit-data', $b->retaceo)],
        ['get', route('retaceos.purchase-data', $b->purchase->id_purchase)],
        ['put', route('retaceos.update', $b->retaceo)],
        ['patch', route('retaceos.updateStatus', $b->retaceo)],
        ['delete', route('retaceos.destroy', $b->retaceo)],

        ['get', route('warehouses.edit', $b->warehouse)],
        ['put', route('warehouses.update', $b->warehouse)],
        ['delete', route('warehouses.destroy', $b->warehouse)],

        ['get', route('locations.show', $b->location)],
        ['get', route('locations.edit', $b->location)],
        ['put', route('locations.update', $b->location)],
        ['delete', route('locations.destroy', $b->location)],

        ['get', route('branches.show', $b->branch)],
        ['get', route('branches.edit', $b->branch)],
        ['put', route('branches.update', $b->branch)],
        ['delete', route('branches.destroy', $b->branch)],

        ['get', route('companies.edit', $b->company)],
        ['put', route('companies.update', $b->company)],
        ['delete', route('companies.destroy', $b->company)],

        ['get', route('users.edit', $b->user)],
        ['put', route('users.update', $b->user)],
        ['delete', route('users.destroy', $b->user)],
    ];

    foreach ($rutas as [$metodo, $url]) {
        $status = $this->{$metodo}($url, ['status' => 'cancelled'])->getStatusCode();
        expect($status)->toBe(404, "{$metodo} {$url} respondió {$status}");
    }

    // Nada de la otra sucursal cambió
    $this->assertDatabaseHas('purchase_requests', ['id_purchase_request' => $b->request->id_purchase_request, 'status' => 'approved']);
    $this->assertDatabaseHas('purchase_orders', ['id_purchase_order' => $b->order->id_purchase_order, 'status' => 'issued']);
    $this->assertDatabaseHas('purchases', ['id_purchase' => $b->purchase->id_purchase, 'status' => 'draft']);
    $this->assertDatabaseHas('retaceos', ['id_retaceo' => $b->retaceo->id_retaceo, 'status' => 'draft']);
    $this->assertDatabaseHas('purchase_quotations', ['id_purchase_quotation' => $b->quotation->id_purchase_quotation]);
    $this->assertDatabaseHas('warehouses', ['id' => $b->warehouse->id, 'is_active' => true]);
    $this->assertDatabaseHas('branches', ['id' => $b->branch->id, 'is_active' => true]);
    $this->assertDatabaseHas('users', ['id' => $b->user->id, 'status' => 'active']);
});

test('los documentos de la propia sucursal se abren con normalidad', function () {
    [$a] = escenarioDosSucursales();
    $this->actingAs($a->user);

    $rutas = [
        route('purchase-quotation-requests.show', $a->quotationRequest->id_purchase_quotation_request),
        route('purchase-quotation-requests.request-details', $a->request->id_purchase_request),
        route('purchase_orders.show', $a->order),
        route('purchase_orders.pdf', $a->order),
        route('purchase_orders.quotation-data', $a->quotation->id_purchase_quotation),
        route('purchases.show', $a->purchase),
        route('purchases.edit', $a->purchase),
        route('purchases.pdf', $a->purchase),
        route('purchases.edit-data', $a->purchase),
        route('purchases.order-data', $a->order->id_purchase_order),
        route('retaceos.show', $a->retaceo),
        route('retaceos.edit', $a->retaceo),
        route('retaceos.pdf', $a->retaceo),
        route('retaceos.edit-data', $a->retaceo),
        route('retaceos.purchase-data', $a->purchase->id_purchase),
        route('locations.show', $a->location),
        route('branches.show', $a->branch),
    ];

    foreach ($rutas as $url) {
        $status = $this->get($url)->getStatusCode();
        expect($status)->toBe(200, "GET {$url} respondió {$status}");
    }
});

// =============================================================================
// Referencias a otra sucursal al crear o editar
// =============================================================================

test('no se pueden crear documentos que referencien datos de otra sucursal', function () {
    [$a, $b, $catalogos] = escenarioDosSucursales();
    $this->actingAs($a->user);

    $detalle = [['id_product' => $catalogos['product']->id, 'quantity' => 1, 'id_unit' => $catalogos['unit']->id, 'unit_price' => 1]];

    // Solicitud de compra en otra sucursal, o con bodega de otra sucursal
    $this->post(route('purchase-requests.store'), [
        'id_branch' => $b->branch->id, 'id_warehouse' => $b->warehouse->id,
        'request_date' => now()->toDateString(), 'required_date' => now()->addDay()->toDateString(),
        'justification' => 'x', 'details' => $detalle,
    ])->assertSessionHasErrors(['id_branch', 'id_warehouse']);

    $this->post(route('purchase-requests.store'), [
        'id_branch' => $a->branch->id, 'id_warehouse' => $b->warehouse->id,
        'request_date' => now()->toDateString(), 'required_date' => now()->addDay()->toDateString(),
        'justification' => 'x', 'details' => $detalle,
    ])->assertSessionHasErrors(['id_warehouse'])->assertSessionDoesntHaveErrors(['id_branch']);

    // Solicitud de cotización sobre una solicitud de otra sucursal
    $this->post(route('purchase-quotation-requests.store'), [
        'id_purchase_request' => $b->request->id_purchase_request,
        'items' => [['id_purchase_request_detail' => $b->requestDetail->id_purchase_request_detail, 'quantity' => 1]],
    ])->assertSessionHasErrors(['id_purchase_request']);

    // Ítems de otra solicitud aunque la solicitud sea propia
    $this->post(route('purchase-quotation-requests.store'), [
        'id_purchase_request' => $a->request->id_purchase_request,
        'items' => [['id_purchase_request_detail' => $b->requestDetail->id_purchase_request_detail, 'quantity' => 1]],
    ])->assertSessionHasErrors(['items.0.id_purchase_request_detail']);

    // Oferta para una solicitud de cotización de otra sucursal
    $this->post(route('purchase-quotations.store'), [
        'id_purchase_quotation_request' => $b->quotationRequest->id_purchase_quotation_request,
        'id_supplier' => $catalogos['supplier']->id_supplier, 'quotation_date' => now()->toDateString(),
        'items' => $detalle,
    ])->assertSessionHasErrors(['id_purchase_quotation_request']);

    // Orden de compra con sucursal, bodega o cotización de otra sucursal
    $this->post(route('purchase_orders.store'), [
        'id_supplier' => $catalogos['supplier']->id_supplier,
        'id_branch' => $b->branch->id, 'id_warehouse' => $b->warehouse->id,
        'id_purchase_quotation' => $b->quotation->id_purchase_quotation,
        'order_date' => now()->toDateString(), 'expected_date' => now()->addDay()->toDateString(),
        'currency' => 'USD', 'payment_terms' => 'Contado', 'products' => $detalle,
    ])->assertSessionHasErrors(['id_branch', 'id_warehouse', 'id_purchase_quotation']);

    // Compra sobre una orden de otra sucursal, o sobre una orden propia con líneas de otra orden
    $compra = fn ($orden) => [
        'id_purchase_order' => $orden->id_purchase_order,
        'id_supplier' => $catalogos['supplier']->id_supplier,
        'purchase_date' => now()->toDateString(), 'status' => 'draft',
        'details' => [[
            'id_product' => $catalogos['product']->id, 'quantity_received' => 1, 'unit_price' => 1,
            'id_purchase_order_detail' => $b->orderDetail->id_purchase_order_detail,
        ]],
    ];
    $this->post(route('purchases.store'), $compra($b->order))->assertSessionHasErrors(['id_purchase_order']);
    $this->post(route('purchases.store'), $compra($a->order))
        ->assertSessionHasErrors(['details.0.id_purchase_order_detail'])
        ->assertSessionDoesntHaveErrors(['id_purchase_order']);

    // Retaceo sobre una compra de otra sucursal, o sobre una compra propia con líneas de otra compra
    $retaceo = fn ($compra) => [
        'id_purchase' => $compra->id_purchase,
        'id_supplier' => $catalogos['supplier']->id_supplier,
        'retaceo_date' => now()->toDateString(), 'status' => 'draft',
        'details' => [[
            'id_product' => $catalogos['product']->id, 'quantity' => 1, 'cost_fob' => 1,
            'id_purchase_detail' => $b->purchaseDetail->id_purchase_detail,
        ]],
    ];
    $this->post(route('retaceos.store'), $retaceo(compraRecibidaSinRetaceo($b)))->assertSessionHasErrors(['id_purchase']);
    $this->post(route('retaceos.store'), $retaceo(compraRecibidaSinRetaceo($a)))
        ->assertSessionHasErrors(['details.0.id_purchase_detail'])
        ->assertSessionDoesntHaveErrors(['id_purchase']);

    // Bodega, ubicación, sucursal y usuario en otra sucursal o empresa
    $this->post(route('warehouses.store'), [
        'branch_id' => $b->branch->id, 'warehouse_category_id' => $b->warehouse->warehouse_category_id, 'name' => 'x',
    ])->assertSessionHasErrors(['branch_id']);

    $this->post(route('locations.store'), [
        'warehouse_id' => $b->warehouse->id, 'code' => 'LOC-'.Str::random(8), 'capacity' => 1,
    ])->assertSessionHasErrors(['warehouse_id']);

    $this->post(route('branches.store'), [
        'company_id' => $b->company->id, 'name' => 'x', 'address' => 'x',
    ])->assertSessionHasErrors(['company_id']);

    $this->post(route('users.store'), [
        'name' => 'x', 'email' => 'x-'.Str::random(6).'@example.com',
        'password' => 'password123', 'password_confirmation' => 'password123',
        'status' => 'active', 'id_branch' => $b->branch->id, 'roles' => [$a->user->roles->first()->id],
    ])->assertSessionHasErrors(['id_branch']);

    // Un usuario que no es admin no puede crear usuarios sin sucursal
    $this->post(route('users.store'), [
        'name' => 'x', 'email' => 'x-'.Str::random(6).'@example.com',
        'password' => 'password123', 'password_confirmation' => 'password123',
        'status' => 'active', 'roles' => [$a->user->roles->first()->id],
    ])->assertSessionHasErrors(['id_branch']);

    expect(PurchaseRequest::queryAllBranches()->where('justification', 'x')->exists())->toBeFalse();
});

test('una oferta solo se puede aceptar dentro de su propia solicitud de cotización', function () {
    [$a] = escenarioDosSucursales();

    $otraSolicitud = PurchaseQuotationRequest::create(['id_purchase_request' => $a->request->id_purchase_request]);

    $this->actingAs($a->user)
        ->patch(route('purchase-quotation-requests.select-quotation', [$otraSolicitud, $a->quotation]))
        ->assertNotFound();

    expect($otraSolicitud->fresh()->id_purchase_quotation)->toBeNull();
});

test('una bodega con documentos no puede pasar a otra sucursal', function () {
    [$a, $b] = escenarioDosSucursales();
    $this->actingAs(administradorGlobal());

    $datos = fn (Warehouse $warehouse, Branch $branch) => [
        'branch_id' => $branch->id, 'warehouse_category_id' => $warehouse->warehouse_category_id, 'name' => $warehouse->name,
    ];

    // La bodega de A tiene solicitudes, órdenes y compras: no se puede mover a B
    $this->put(route('warehouses.update', $a->warehouse), $datos($a->warehouse, $b->branch))
        ->assertSessionHasErrors(['branch_id']);
    $this->assertDatabaseHas('warehouses', ['id' => $a->warehouse->id, 'branch_id' => $a->branch->id]);

    // Una bodega sin documentos sí se puede mover
    $vacia = Warehouse::create(['branch_id' => $a->branch->id, 'warehouse_category_id' => $a->warehouse->warehouse_category_id, 'name' => 'Vacía']);
    $this->put(route('warehouses.update', $vacia), $datos($vacia, $b->branch))->assertSessionHasNoErrors();
    $this->assertDatabaseHas('warehouses', ['id' => $vacia->id, 'branch_id' => $b->branch->id]);
});

test('una compra siempre queda en la sucursal de su orden', function () {
    [$a, $b, $catalogos] = escenarioDosSucursales();

    $this->actingAs(administradorGlobal())->post(route('purchases.store'), [
        'id_purchase_order' => $a->order->id_purchase_order,
        'id_supplier'       => $catalogos['supplier']->id_supplier,
        'id_branch'         => $b->branch->id,
        'purchase_date'     => now()->toDateString(),
        'status'            => 'draft',
        'details'           => [['id_product' => $catalogos['product']->id, 'quantity_received' => 1, 'unit_price' => 1]],
    ])->assertSessionHasNoErrors();

    $compra = Purchase::where('id_purchase_order', $a->order->id_purchase_order)->latest('id_purchase')->first();
    expect($compra->id_branch)->toBe($a->branch->id);
});

// =============================================================================
// Usuario sin sucursal, administrador y correlativos
// =============================================================================

test('un usuario que no es admin y no tiene sucursal no ve ningún dato', function () {
    [$a] = escenarioDosSucursales();
    $this->actingAs(usuarioDeSucursal(null));

    expect($this->get(route('purchase-requests.index'))->viewData('purchaseRequests')->total())->toBe(0)
        ->and($this->get(route('purchase_orders.index'))->viewData('purchase_orders')->total())->toBe(0)
        ->and($this->get(route('warehouses.index'))->viewData('warehouses'))->toHaveCount(0)
        ->and($this->get(route('branches.index'))->viewData('branches'))->toHaveCount(0)
        ->and($this->get(route('users.index'))->viewData('users')->total())->toBe(0);

    $this->get(route('purchase_orders.show', $a->order))->assertNotFound();
});

test('el administrador ve los datos de todas las sucursales', function () {
    [$a, $b] = escenarioDosSucursales();
    $this->actingAs(administradorGlobal());

    $ids = collect($this->get(route('purchase-requests.index'))->viewData('purchaseRequests')->items())
        ->pluck('id_purchase_request');
    expect($ids)->toContain($a->request->id_purchase_request)->toContain($b->request->id_purchase_request);

    $this->get(route('purchase_orders.show', $b->order))->assertOk();
    $this->get(route('retaceos.show', $b->retaceo))->assertOk();
});

test('los correlativos siguen siendo únicos aunque el usuario solo vea su sucursal', function () {
    [$a, $b, $catalogos] = escenarioDosSucursales();

    // El último documento de cada tipo pertenece a la sucursal B; el usuario de A no lo ve
    $this->actingAs($a->user);

    $orden = PurchaseOrder::create([
        'id_supplier' => $catalogos['supplier']->id_supplier, 'id_branch' => $a->branch->id,
        'id_warehouse' => $a->warehouse->id, 'order_date' => now(), 'status' => 'draft',
    ]);
    $compra = Purchase::create([
        'id_purchase_order' => $a->order->id_purchase_order, 'id_supplier' => $catalogos['supplier']->id_supplier,
        'id_branch' => $a->branch->id, 'purchase_date' => now(), 'status' => 'draft',
    ]);
    $retaceo = Retaceo::create([
        'id_supplier' => $catalogos['supplier']->id_supplier, 'id_purchase' => $a->purchase->id_purchase,
        'retaceo_date' => now(), 'status' => 'draft',
    ]);
    $cotizacion = PurchaseQuotation::create([
        'id_purchase_quotation_request' => $a->quotationRequest->id_purchase_quotation_request,
        'id_supplier' => $catalogos['supplier']->id_supplier, 'quotation_date' => now(),
    ]);

    expect($orden->purchase_order_code)->not->toBe($b->order->purchase_order_code)
        ->and($compra->purchase_code)->not->toBe($b->purchase->purchase_code)
        ->and($retaceo->retaceo_code)->not->toBe($b->retaceo->retaceo_code)
        ->and($cotizacion->purchase_quotation_code)->not->toBe($b->quotation->purchase_quotation_code);

    // Solicitud de compra (el correlativo se genera en el controlador)
    $codigoB = PurchaseRequest::queryAllBranches()->create([
        'uuid' => (string) Str::uuid(), 'purchase_request_code' => 'REQ-'.now()->year.'-9998',
        'id_branch' => $b->branch->id, 'id_warehouse' => $b->warehouse->id, 'id_user' => $b->user->id,
        'request_date' => now(), 'required_date' => now(), 'justification' => 'B',
    ])->purchase_request_code;

    $this->post(route('purchase-requests.store'), [
        'id_branch' => $a->branch->id, 'id_warehouse' => $a->warehouse->id,
        'request_date' => now()->toDateString(), 'required_date' => now()->addDay()->toDateString(),
        'justification' => 'Solicitud A',
        'details' => [['id_product' => $catalogos['product']->id, 'quantity' => 1, 'id_unit' => $catalogos['unit']->id]],
    ])->assertSessionHasNoErrors();

    $codigoA = PurchaseRequest::where('justification', 'Solicitud A')->value('purchase_request_code');
    expect($codigoA)->toBe('REQ-'.now()->year.'-9999')->not->toBe($codigoB);
});
