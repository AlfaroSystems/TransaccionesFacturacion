<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Product;
use App\Models\PurchaseQuotationRequest;
use App\Models\PurchaseRequest;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseCategory;
use Illuminate\Support\Str;

// =============================================================================
// Flujo: la sucursal crea y envía; el departamento de compras (Casa Matriz) la aprueba,
// devuelve o rechaza, y de las aprobadas genera la cotización. Una vez enviada, nadie
// la modifica.
// =============================================================================

const PERMISOS_SUCURSAL_SOLICITUDES = [
    'purchase_requests.ver', 'purchase_requests.crear', 'purchase_requests.editar',
    'purchase_requests.eliminar', 'purchase_requests.enviar',
];

const PERMISOS_COMPRAS_SOLICITUDES = [
    'purchase_requests.ver', 'purchase_requests.crear', 'purchase_requests.editar',
    'purchase_requests.eliminar', 'purchase_requests.enviar', 'purchase_requests.aprobar', 'purchase_requests.devolver',
    'purchase_quotation_requests.ver', 'purchase_quotation_requests.crear',
];

function flujoUsuario(Branch $branch, array $permisos): User
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

function flujoEscenario(): object
{
    $tag = Str::upper(Str::random(5));
    $category = WarehouseCategory::create(['name' => "Categoría {$tag}"]);
    $unit = Unit::create(['name' => "Unidad {$tag}", 'abbreviation' => 'UND']);
    $product = Product::create(['name' => "Producto {$tag}", 'sku' => "SKU-{$tag}"]);

    $company = Company::create(['name' => "Empresa {$tag}"]);
    $sucursal = fn (Company $company, string $name, bool $compras = false) => Branch::create([
        'id_company' => $company->id_company, 'name' => "{$name} {$tag}", 'is_purchasing_department' => $compras,
    ]);
    $bodega = fn (Branch $branch) => Warehouse::create([
        'id_branch' => $branch->id_branch, 'id_warehouse_category' => $category->id_warehouse_category, 'name' => "Bodega {$branch->name}",
    ]);

    $matriz = $sucursal($company, 'Casa Matriz', true);
    $sucursalA = $sucursal($company, 'Sucursal A');
    $sucursalB = $sucursal($company, 'Sucursal B');
    $otraEmpresa = $sucursal(Company::create(['name' => "Otra empresa {$tag}"]), 'Sucursal C');

    return (object) [
        'product' => $product, 'unit' => $unit,
        'matriz' => $matriz, 'bodegaMatriz' => $bodega($matriz),
        'sucursalA' => $sucursalA, 'bodegaA' => $bodega($sucursalA),
        'sucursalB' => $sucursalB, 'bodegaB' => $bodega($sucursalB),
        'otraEmpresa' => $otraEmpresa, 'bodegaOtraEmpresa' => $bodega($otraEmpresa),
        'compras' => flujoUsuario($matriz, PERMISOS_COMPRAS_SOLICITUDES),
        'usuarioA' => flujoUsuario($sucursalA, PERMISOS_SUCURSAL_SOLICITUDES),
        'usuarioB' => flujoUsuario($sucursalB, PERMISOS_SUCURSAL_SOLICITUDES),
    ];
}

function flujoDatos(object $e, Branch $branch, Warehouse $warehouse, array $extra = []): array
{
    return array_merge([
        'id_branch' => $branch->id_branch,
        'id_warehouse' => $warehouse->id_warehouse,
        'request_date' => now()->format('Y-m-d H:i'),
        'required_date' => now()->addWeek()->format('Y-m-d H:i'),
        'justification' => 'Reposición de inventario',
        'details' => [['id_product' => $e->product->id_product, 'quantity' => 3, 'id_unit' => $e->unit->id_unit]],
    ], $extra);
}

/** Crea una solicitud directamente en la base, con el estado indicado */
function flujoSolicitud(object $e, Branch $branch, Warehouse $warehouse, User $user, string $status): PurchaseRequest
{
    $request = PurchaseRequest::queryAllBranches()->create([
        'uuid' => (string) Str::uuid(),
        'purchase_request_code' => 'REQ-FLUJO-'.Str::upper(Str::random(6)),
        'id_branch' => $branch->id_branch,
        'id_warehouse' => $warehouse->id_warehouse,
        'id_user' => $user->id_user,
        'request_date' => now(),
        'required_date' => now()->addWeek(),
        'justification' => 'Solicitud de prueba',
        'status' => $status,
        'sent_at' => $status === 'draft' ? null : now(),
    ]);
    $request->details()->create(['id_product' => $e->product->id_product, 'quantity' => 2, 'id_unit' => $e->unit->id_unit]);

    return $request;
}

function flujoVisibles(User $user): array
{
    test()->actingAs($user);

    return test()->get(route('purchase-requests.index'))->assertOk()
        ->viewData('purchaseRequests')->pluck('id_purchase_request')->all();
}

test('la sucursal crea un borrador y lo envía; después nadie puede modificarlo ni eliminarlo', function () {
    $e = flujoEscenario();

    $this->actingAs($e->usuarioA)
        ->post(route('purchase-requests.store'), flujoDatos($e, $e->sucursalA, $e->bodegaA, ['send' => 0]))
        ->assertSessionHasNoErrors();
    $request = PurchaseRequest::queryAllBranches()->where('id_branch', $e->sucursalA->id_branch)->sole();
    expect($request->status)->toBe('draft')->and($request->sent_at)->toBeNull();

    $this->post(route('purchase-requests.send', $request))->assertSessionHas('success');
    $request->refresh();
    expect($request->status)->toBe('sent')->and($request->sent_at)->not->toBeNull();

    // Ni la sucursal ni compras pueden editarla o eliminarla
    foreach ([$e->usuarioA, $e->compras] as $user) {
        $this->actingAs($user)->put(route('purchase-requests.update', $request), flujoDatos($e, $e->sucursalA, $e->bodegaA, ['justification' => 'Cambiada']));
        $this->actingAs($user)->delete(route('purchase-requests.destroy', $request));
    }

    $this->assertDatabaseHas('purchase_requests', [
        'id_purchase_request' => $request->id_purchase_request, 'status' => 'sent', 'justification' => 'Reposición de inventario',
    ]);
});

test('compras ve lo que le envían las sucursales de su empresa, no sus borradores ni otra empresa', function () {
    $e = flujoEscenario();
    $borradorA = flujoSolicitud($e, $e->sucursalA, $e->bodegaA, $e->usuarioA, 'draft');
    $enviadaA = flujoSolicitud($e, $e->sucursalA, $e->bodegaA, $e->usuarioA, 'sent');
    $devueltaA = flujoSolicitud($e, $e->sucursalA, $e->bodegaA, $e->usuarioA, 'returned');
    $enviadaB = flujoSolicitud($e, $e->sucursalB, $e->bodegaB, $e->usuarioB, 'sent');
    $borradorMatriz = flujoSolicitud($e, $e->matriz, $e->bodegaMatriz, $e->compras, 'draft');
    $otraEmpresa = flujoSolicitud($e, $e->otraEmpresa, $e->bodegaOtraEmpresa, $e->usuarioA, 'sent');

    expect(flujoVisibles($e->compras))
        ->toContain($enviadaA->id_purchase_request, $enviadaB->id_purchase_request, $borradorMatriz->id_purchase_request)
        ->not->toContain($borradorA->id_purchase_request)
        ->not->toContain($devueltaA->id_purchase_request)
        ->not->toContain($otraEmpresa->id_purchase_request);

    // Una sucursal normal solo ve las suyas, en cualquier estado
    expect(flujoVisibles($e->usuarioA))
        ->toContain($borradorA->id_purchase_request, $enviadaA->id_purchase_request, $devueltaA->id_purchase_request)
        ->not->toContain($enviadaB->id_purchase_request)
        ->not->toContain($borradorMatriz->id_purchase_request);

    $this->actingAs($e->compras)->get(route('purchase-requests.show', $borradorA))->assertNotFound();

    // Compras ve la sucursal y la bodega de las solicitudes que recibe
    $this->actingAs($e->compras)->get(route('purchase-requests.index'))->assertSee($e->sucursalA->name)->assertSee($e->bodegaA->name);
});

test('compras devuelve con motivo; la sucursal la corrige y la reenvía', function () {
    $e = flujoEscenario();
    $request = flujoSolicitud($e, $e->sucursalA, $e->bodegaA, $e->usuarioA, 'sent');

    // El motivo es obligatorio
    $this->actingAs($e->compras)->post(route('purchase-requests.return', $request), ['reason' => '  '])->assertSessionHas('error');
    expect($request->fresh()->status)->toBe('sent');

    $this->post(route('purchase-requests.return', $request), ['reason' => 'Falta indicar la marca'])->assertSessionHas('success');
    $request->refresh();
    expect($request->status)->toBe('returned')->and($request->status_reason)->toBe('Falta indicar la marca');

    // Devuelta, compras ya no la ve; la sucursal sí y puede corregirla
    expect(flujoVisibles($e->compras))->not->toContain($request->id_purchase_request);

    $this->actingAs($e->usuarioA)
        ->put(route('purchase-requests.update', $request), flujoDatos($e, $e->sucursalA, $e->bodegaA, ['justification' => 'Marca X']))
        ->assertSessionHasNoErrors();
    $this->post(route('purchase-requests.send', $request))->assertSessionHas('success');

    $request->refresh();
    expect($request->status)->toBe('sent')
        ->and($request->justification)->toBe('Marca X')
        ->and($request->status_reason)->toBeNull();
});

test('compras rechaza con motivo de forma definitiva', function () {
    $e = flujoEscenario();
    $request = flujoSolicitud($e, $e->sucursalA, $e->bodegaA, $e->usuarioA, 'sent');

    $this->actingAs($e->compras)->post(route('purchase-requests.reject', $request), ['reason' => 'Producto descontinuado'])->assertSessionHas('success');

    $request->refresh();
    expect($request->status)->toBe('rejected')->and($request->status_reason)->toBe('Producto descontinuado');

    // La sucursal no puede reenviarla ni editarla, y compras no puede devolverla después
    $this->actingAs($e->usuarioA)->post(route('purchase-requests.send', $request))->assertSessionHas('error');
    $this->actingAs($e->compras)->post(route('purchase-requests.return', $request), ['reason' => 'x'])->assertSessionHas('error');
    expect($request->fresh()->status)->toBe('rejected');
});

test('la sucursal no puede devolver ni rechazar: es una acción de compras', function () {
    $e = flujoEscenario();
    $request = flujoSolicitud($e, $e->sucursalA, $e->bodegaA, $e->usuarioA, 'sent');

    $this->actingAs($e->usuarioA)->post(route('purchase-requests.reject', $request), ['reason' => 'x'])->assertForbidden();
    expect($request->fresh()->status)->toBe('sent');
});

test('compras crea solicitudes para otra sucursal, que quedan enviadas; las suyas pueden ser borrador', function () {
    $e = flujoEscenario();
    $this->actingAs($e->compras);

    $this->post(route('purchase-requests.store'), flujoDatos($e, $e->sucursalA, $e->bodegaA, ['send' => 0]))->assertSessionHasNoErrors();
    $paraA = PurchaseRequest::queryAllBranches()->where('id_branch', $e->sucursalA->id_branch)->sole();
    expect($paraA->status)->toBe('sent')->and($paraA->id_user)->toBe($e->compras->id_user);

    $this->post(route('purchase-requests.store'), flujoDatos($e, $e->matriz, $e->bodegaMatriz, ['send' => 0]))->assertSessionHasNoErrors();
    expect(PurchaseRequest::queryAllBranches()->where('id_branch', $e->matriz->id_branch)->sole()->status)->toBe('draft');

    // No puede crear para una sucursal de otra empresa ni con una bodega de otra sucursal
    $this->post(route('purchase-requests.store'), flujoDatos($e, $e->otraEmpresa, $e->bodegaOtraEmpresa))->assertSessionHasErrors('id_branch');
    $this->post(route('purchase-requests.store'), flujoDatos($e, $e->sucursalA, $e->bodegaB))->assertSessionHasErrors('id_warehouse');

    // La sucursal la ve, pero no puede modificarla
    expect(flujoVisibles($e->usuarioA))->toContain($paraA->id_purchase_request);
    $this->actingAs($e->usuarioA)->delete(route('purchase-requests.destroy', $paraA));
    expect($paraA->fresh())->not->toBeNull();
});

test('una sucursal normal solo puede crear solicitudes para sí misma', function () {
    $e = flujoEscenario();

    $this->actingAs($e->usuarioA)
        ->post(route('purchase-requests.store'), flujoDatos($e, $e->sucursalB, $e->bodegaB))
        ->assertSessionHasErrors('id_branch');
});

test('compras aprueba una solicitud de otra sucursal, genera su cotización y la sigue viendo', function () {
    $e = flujoEscenario();
    $request = flujoSolicitud($e, $e->sucursalA, $e->bodegaA, $e->usuarioA, 'sent');
    $cotizar = fn () => $this->post(route('purchase-quotation-requests.store'), [
        'purchase_requests' => [$request->id_purchase_request],
    ]);

    // Enviada aún no se puede cotizar: primero se aprueba
    $this->actingAs($e->compras);
    $cotizar()->assertSessionHasErrors('purchase_requests.0');

    $this->post(route('purchase-requests.approve', $request))->assertSessionHas('success');
    expect($request->fresh()->status)->toBe('approved');

    // Aprobada ya no se devuelve ni se rechaza
    $this->post(route('purchase-requests.return', $request), ['reason' => 'x'])->assertSessionHas('error');
    $this->post(route('purchase-requests.reject', $request), ['reason' => 'x'])->assertSessionHas('error');
    expect($request->fresh()->status)->toBe('approved');

    $cotizar()->assertSessionHasNoErrors();

    expect($request->fresh()->status)->toBe('quoted');

    $quotationRequests = $this->get(route('purchase-quotation-requests.index'))->assertOk()->viewData('quotationRequests');
    expect($quotationRequests->flatMap(fn ($qr) => $qr->purchaseRequests->pluck('id_purchase_request')))->toContain($request->id_purchase_request);

    // La sucursal ve su solicitud en cotización, pero no la solicitud de otra sucursal
    expect(flujoVisibles($e->usuarioB))->not->toContain($request->id_purchase_request);
    expect(PurchaseQuotationRequest::queryAllBranches()->count())->toBe(1);
});

test('solo el administrador puede marcar la sucursal del departamento de compras', function () {
    $e = flujoEscenario();
    $gerente = flujoUsuario($e->sucursalA, ['branches.ver', 'branches.editar']);

    $datos = ['id_company' => $e->sucursalA->id_company, 'name' => $e->sucursalA->name, 'addres' => 'Dirección', 'is_active' => 1, 'is_purchasing_department' => 1];

    $this->actingAs($gerente)->put(route('branches.update', $e->sucursalA), $datos)->assertSessionHasErrors('is_purchasing_department');
    expect($e->sucursalA->fresh()->is_purchasing_department)->toBeFalse();

    $admin = User::factory()->create();
    $admin->roles()->attach(Role::firstOrCreate(['name' => 'admin'])->id_role, ['assigned_at' => now()]);

    $this->actingAs($admin)->put(route('branches.update', $e->sucursalA), $datos)->assertSessionHasNoErrors();
    expect($e->sucursalA->fresh()->is_purchasing_department)->toBeTrue();
});

test('el formulario de nueva solicitud trae seleccionada la sucursal del usuario', function () {
    $e = flujoEscenario();

    $this->actingAs($e->usuarioA)->get(route('purchase-requests.index'))
        ->assertSee('<option value="'.$e->sucursalA->id_branch.'" selected>', false);

    // Compras puede elegir otra sucursal, pero de entrada tiene la suya
    $this->actingAs($e->compras)->get(route('purchase-requests.index'))
        ->assertSee('<option value="'.$e->matriz->id_branch.'" selected>', false)
        ->assertDontSee('<option value="'.$e->sucursalA->id_branch.'" selected>', false);
});

test('al crear no se acepta una fecha requerida anterior a hoy; al corregir una devuelta sí', function () {
    $e = flujoEscenario();
    $ayer = now()->subDay()->format('Y-m-d H:i');

    $this->actingAs($e->usuarioA)
        ->post(route('purchase-requests.store'), flujoDatos($e, $e->sucursalA, $e->bodegaA, [
            'request_date' => now()->subDays(2)->format('Y-m-d H:i'), 'required_date' => $ayer,
        ]))
        ->assertSessionHasErrors(['required_date' => 'La fecha requerida no puede ser anterior a hoy.']);

    // Hoy sí se acepta
    $this->post(route('purchase-requests.store'), flujoDatos($e, $e->sucursalA, $e->bodegaA, [
        'request_date' => now()->startOfDay()->format('Y-m-d H:i'), 'required_date' => now()->startOfDay()->format('Y-m-d H:i'),
    ]))->assertSessionHasNoErrors();

    $devuelta = flujoSolicitud($e, $e->sucursalA, $e->bodegaA, $e->usuarioA, 'returned');
    $this->put(route('purchase-requests.update', $devuelta), flujoDatos($e, $e->sucursalA, $e->bodegaA, [
        'request_date' => now()->subDays(2)->format('Y-m-d H:i'), 'required_date' => $ayer,
    ]))->assertSessionHasNoErrors();
});

test('compras ve las sucursales y bodegas de su empresa, pero solo modifica las suyas', function () {
    $e = flujoEscenario();
    $permisos = [
        'branches.ver', 'branches.crear', 'branches.editar', 'branches.eliminar',
        'warehouses.ver', 'warehouses.crear', 'warehouses.editar', 'warehouses.eliminar',
    ];
    $compras = flujoUsuario($e->matriz, $permisos);
    $this->actingAs($compras);

    $sucursales = $this->get(route('branches.index'))->assertOk()
        ->assertSee(route('branches.update', $e->matriz))
        ->assertDontSee(route('branches.update', $e->sucursalA))
        ->viewData('branches')->pluck('id_branch');
    expect($sucursales)->toContain($e->matriz->id_branch, $e->sucursalA->id_branch, $e->sucursalB->id_branch)
        ->not->toContain($e->otraEmpresa->id_branch);

    $bodegas = $this->get(route('warehouses.index'))->assertOk()
        ->assertSee($e->sucursalA->name)
        ->assertSee(route('warehouses.update', $e->bodegaMatriz->id_warehouse))
        ->assertDontSee(route('warehouses.update', $e->bodegaA->id_warehouse))
        ->viewData('warehouses')->pluck('id_warehouse');
    expect($bodegas)->toContain($e->bodegaMatriz->id_warehouse, $e->bodegaA->id_warehouse)
        ->not->toContain($e->bodegaOtraEmpresa->id_warehouse);

    // No puede modificar ni crear en otra sucursal
    $this->put(route('branches.update', $e->sucursalA), ['id_company' => $e->sucursalA->id_company, 'name' => 'Cambiada', 'addres' => 'x'])->assertNotFound();
    $this->delete(route('warehouses.destroy', $e->bodegaA->id_warehouse))->assertNotFound();
    $this->post(route('warehouses.store'), [
        'id_branch' => $e->sucursalA->id_branch, 'id_warehouse_category' => $e->bodegaA->id_warehouse_category, 'name' => 'Bodega ajena',
    ])->assertSessionHasErrors('id_branch');
    expect($e->sucursalA->fresh()->name)->not->toBe('Cambiada');

    // Una sucursal normal sigue viendo solo lo suyo
    $gerente = flujoUsuario($e->sucursalA, $permisos);
    expect($this->actingAs($gerente)->get(route('branches.index'))->viewData('branches')->pluck('id_branch')->all())
        ->toBe([$e->sucursalA->id_branch]);
});

test('un producto no puede repetirse en las filas de una solicitud', function () {
    $e = flujoEscenario();
    $fila = ['id_product' => $e->product->id_product, 'quantity' => 1, 'id_unit' => $e->unit->id_unit];

    $this->actingAs($e->usuarioA)
        ->post(route('purchase-requests.store'), flujoDatos($e, $e->sucursalA, $e->bodegaA, ['details' => [$fila, $fila]]))
        ->assertSessionHasErrors(['details.1.id_product' => 'Un producto aparece en más de una fila; indique la cantidad total en una sola.']);

    expect(PurchaseRequest::queryAllBranches()->where('id_branch', $e->sucursalA->id_branch)->exists())->toBeFalse();
});

test('solo quien tiene el permiso de aprobar puede aprobar, y solo solicitudes enviadas', function () {
    $e = flujoEscenario();
    $enviada = flujoSolicitud($e, $e->sucursalA, $e->bodegaA, $e->usuarioA, 'sent');
    $borrador = flujoSolicitud($e, $e->matriz, $e->bodegaMatriz, $e->compras, 'draft');

    $this->actingAs($e->usuarioA)->post(route('purchase-requests.approve', $enviada))->assertForbidden();
    expect($enviada->fresh()->status)->toBe('sent');

    $this->actingAs($e->compras)->post(route('purchase-requests.approve', $borrador))->assertSessionHas('error');
    expect($borrador->fresh()->status)->toBe('draft');
});

test('una solicitud de cotización reúne varias solicitudes de compra y suma los productos repetidos', function () {
    $e = flujoEscenario();
    $deA = flujoSolicitud($e, $e->sucursalA, $e->bodegaA, $e->usuarioA, 'approved');
    $deB = flujoSolicitud($e, $e->sucursalB, $e->bodegaB, $e->usuarioB, 'approved');
    $otroProducto = Product::create(['name' => 'Monitor '.Str::random(4), 'sku' => 'SKU-'.Str::random(6)]);
    $deB->details()->create(['id_product' => $otroProducto->id_product, 'quantity' => 7, 'id_unit' => $e->unit->id_unit]);

    $this->actingAs($e->compras)->post(route('purchase-quotation-requests.store'), [
        'purchase_requests' => [$deA->id_purchase_request, $deB->id_purchase_request],
    ])->assertSessionHasNoErrors()->assertSessionHas('success');

    expect($deA->fresh()->status)->toBe('quoted')->and($deB->fresh()->status)->toBe('quoted');

    // Una sola solicitud de cotización con las 3 líneas de las dos solicitudes
    $quotationRequest = PurchaseQuotationRequest::queryAllBranches()->sole();
    expect($quotationRequest->details()->count())->toBe(3);

    $quotationRequest->load('details.purchaseRequestDetail.purchaseRequest', 'details.purchaseRequestDetail.product', 'details.purchaseRequestDetail.unit');
    expect($quotationRequest->purchaseRequests->pluck('id_purchase_request')->sort()->values()->all())
        ->toBe(collect([$deA->id_purchase_request, $deB->id_purchase_request])->sort()->values()->all());

    // El producto que piden ambas va en una línea con el total (2 + 2) y su desglose
    $lineas = $quotationRequest->quotationLines()->keyBy(fn ($line) => $line->product->id_product);
    expect($lineas)->toHaveCount(2)
        ->and($lineas[$e->product->id_product]->quantity)->toBe(4.0)
        ->and($lineas[$e->product->id_product]->sources)->toHaveCount(2)
        ->and($lineas[$otroProducto->id_product]->quantity)->toBe(7.0);

    $this->get(route('purchase-quotation-requests.show', $quotationRequest->id_purchase_quotation_request))
        ->assertOk()
        ->assertSee($deA->purchase_request_code)
        ->assertSee($deB->purchase_request_code)
        ->assertSee('4.00');
});

test('no se genera la solicitud de cotización si alguna de las solicitudes no está aprobada', function () {
    $e = flujoEscenario();
    $aprobada = flujoSolicitud($e, $e->sucursalA, $e->bodegaA, $e->usuarioA, 'approved');
    $enviada = flujoSolicitud($e, $e->sucursalB, $e->bodegaB, $e->usuarioB, 'sent');

    $this->actingAs($e->compras)->post(route('purchase-quotation-requests.store'), [
        'purchase_requests' => [$aprobada->id_purchase_request, $enviada->id_purchase_request],
    ])->assertSessionHasErrors('purchase_requests.1');

    expect(PurchaseQuotationRequest::queryAllBranches()->count())->toBe(0)
        ->and($aprobada->fresh()->status)->toBe('approved');
});

/** Solicitud de cotización con dos solicitudes (producto repetido y uno extra) y ofertas de dos proveedores */
function flujoCotizacionConOfertas(object $e): object
{
    $deA = flujoSolicitud($e, $e->sucursalA, $e->bodegaA, $e->usuarioA, 'quoted');
    $deB = flujoSolicitud($e, $e->sucursalB, $e->bodegaB, $e->usuarioB, 'quoted');
    $monitor = Product::create(['name' => 'Monitor '.Str::random(4), 'sku' => 'SKU-'.Str::random(6)]);
    $deB->details()->create(['id_product' => $monitor->id_product, 'quantity' => 3, 'id_unit' => $e->unit->id_unit]);

    $quotationRequest = PurchaseQuotationRequest::createFromPurchaseRequests(collect([$deA->load('details'), $deB->load('details')]));

    $oferta = function (string $name, float $precioProducto, float $precioMonitor) use ($e, $quotationRequest, $monitor) {
        $supplier = \App\Models\Supplier::create(['name' => $name.' '.Str::random(4), 'email' => Str::random(8).'@example.com', 'country' => 'El Salvador', 'is_active' => true]);
        $quotation = \App\Models\PurchaseQuotation::create([
            'id_purchase_quotation_request' => $quotationRequest->id_purchase_quotation_request,
            'id_supplier' => $supplier->id_supplier, 'quotation_date' => now(), 'status' => 'submitted',
        ]);
        $quotation->details()->create(['id_product' => $e->product->id_product, 'id_unit' => $e->unit->id_unit, 'quantity' => 4, 'unit_price' => $precioProducto, 'total' => 4 * $precioProducto]);
        $quotation->details()->create(['id_product' => $monitor->id_product, 'id_unit' => $e->unit->id_unit, 'quantity' => 3, 'unit_price' => $precioMonitor, 'total' => 3 * $precioMonitor]);

        return $quotation->load('details');
    };

    $admin = User::factory()->create();
    $admin->roles()->attach(Role::firstOrCreate(['name' => 'admin'])->id_role, ['assigned_at' => now()]);

    return (object) [
        'quotationRequest' => $quotationRequest, 'monitor' => $monitor, 'admin' => $admin,
        'ofertaUno' => $oferta('Proveedor Uno', 10, 20),
        'ofertaDos' => $oferta('Proveedor Dos', 8, 25),
        'claveProducto' => $e->product->id_product.'-'.$e->unit->id_unit,
        'claveMonitor' => $monitor->id_product.'-'.$e->unit->id_unit,
    ];
}

test('se puede adjudicar cada producto a un proveedor distinto y cada orden lleva solo lo suyo', function () {
    $e = flujoEscenario();
    $c = flujoCotizacionConOfertas($e);
    $linea = fn ($oferta, $producto) => $oferta->details->firstWhere('id_product', $producto)->id_purchase_quotation_detail;

    $this->actingAs($c->admin)->get(route('purchase-quotation-requests.show', $c->quotationRequest->id_purchase_quotation_request))
        ->assertOk()->assertSee('Adjudicación por Producto')->assertSee('Todo de este proveedor')->assertSee('name="awards['.$c->claveMonitor.']"', false);

    // El producto (de las dos solicitudes) al Proveedor Dos; el monitor al Proveedor Uno
    $this->actingAs($c->admin)->post(route('purchase-quotation-requests.award', $c->quotationRequest->id_purchase_quotation_request), [
        'awards' => [
            $c->claveProducto => $linea($c->ofertaDos, $e->product->id_product),
            $c->claveMonitor => $linea($c->ofertaUno, $c->monitor->id_product),
        ],
    ])->assertSessionHas('success');

    $detalles = $c->quotationRequest->details()->with('purchaseRequestDetail')->get();
    foreach ($detalles as $detalle) {
        $esperada = $detalle->purchaseRequestDetail->id_product === $c->monitor->id_product
            ? $linea($c->ofertaUno, $c->monitor->id_product)
            : $linea($c->ofertaDos, $e->product->id_product);
        expect($detalle->id_purchase_quotation_detail)->toBe($esperada);
    }

    // Las dos ofertas ganaron algo; con dos ganadores no hay una sola cotización asociada
    expect($c->ofertaUno->fresh()->status)->toBe('approved')
        ->and($c->ofertaDos->fresh()->status)->toBe('approved')
        ->and($c->quotationRequest->fresh()->id_purchase_quotation)->toBeNull()
        ->and($c->quotationRequest->fresh()->isAwarded())->toBeTrue();

    $this->get(route('purchase-quotation-requests.show', $c->quotationRequest->id_purchase_quotation_request))
        ->assertOk()->assertSee('Adjudicada: 1 de 2 productos')->assertDontSee('Guardar Adjudicación');

    // No se adjudica dos veces
    $this->post(route('purchase-quotation-requests.award', $c->quotationRequest->id_purchase_quotation_request), [
        'awards' => [
            $c->claveProducto => $linea($c->ofertaUno, $e->product->id_product),
            $c->claveMonitor => $linea($c->ofertaUno, $c->monitor->id_product),
        ],
    ])->assertSessionHas('error', 'Esta solicitud de cotización ya fue adjudicada.');
});

test('todo a un proveedor deja la otra oferta rechazada y la cotización asociada', function () {
    $e = flujoEscenario();
    $c = flujoCotizacionConOfertas($e);

    $this->actingAs($c->admin)->post(route('purchase-quotation-requests.award', $c->quotationRequest->id_purchase_quotation_request), [
        'awards' => [
            $c->claveProducto => $c->ofertaUno->details->firstWhere('id_product', $e->product->id_product)->id_purchase_quotation_detail,
            $c->claveMonitor => $c->ofertaUno->details->firstWhere('id_product', $c->monitor->id_product)->id_purchase_quotation_detail,
        ],
    ])->assertSessionHas('success');

    expect($c->ofertaUno->fresh()->status)->toBe('approved')
        ->and($c->ofertaDos->fresh()->status)->toBe('rejected')
        ->and($c->quotationRequest->fresh()->id_purchase_quotation)->toBe($c->ofertaUno->id_purchase_quotation);
});

test('la adjudicación exige un proveedor para cada producto y una línea del mismo producto', function () {
    $e = flujoEscenario();
    $c = flujoCotizacionConOfertas($e);
    $this->actingAs($c->admin);
    $adjudicar = fn (array $awards) => $this->post(route('purchase-quotation-requests.award', $c->quotationRequest->id_purchase_quotation_request), ['awards' => $awards]);

    // Falta el monitor
    $adjudicar([$c->claveProducto => $c->ofertaUno->details->firstWhere('id_product', $e->product->id_product)->id_purchase_quotation_detail])
        ->assertSessionHas('error');

    // La línea elegida para el producto es la del monitor
    $adjudicar([
        $c->claveProducto => $c->ofertaUno->details->firstWhere('id_product', $c->monitor->id_product)->id_purchase_quotation_detail,
        $c->claveMonitor => $c->ofertaUno->details->firstWhere('id_product', $c->monitor->id_product)->id_purchase_quotation_detail,
    ])->assertSessionHas('error');

    expect($c->quotationRequest->fresh()->isAwarded())->toBeFalse()
        ->and($c->ofertaUno->fresh()->status)->toBe('submitted');
});

test('las órdenes se generan por proveedor y por sucursal que pidió, con su parte del flete', function () {
    $e = flujoEscenario();
    $c = flujoCotizacionConOfertas($e);
    $linea = fn ($oferta, $producto) => $oferta->details->firstWhere('id_product', $producto)->id_purchase_quotation_detail;
    $flete = \App\Models\ExpenseType::create(['name' => 'Flete '.Str::random(4)]);
    $c->ofertaDos->expenses()->create(['id_expense_type' => $flete->id_expense_type, 'description' => 'Envío', 'amount' => 25]);

    $this->actingAs($c->admin);

    // Sin adjudicar no se generan
    $this->post(route('purchase-quotation-requests.generate-orders', $c->quotationRequest->id_purchase_quotation_request))
        ->assertSessionHas('error');

    // El producto (lo piden A y B) al Proveedor Dos; el monitor (solo B) al Proveedor Uno
    $this->post(route('purchase-quotation-requests.award', $c->quotationRequest->id_purchase_quotation_request), [
        'awards' => [
            $c->claveProducto => $linea($c->ofertaDos, $e->product->id_product),
            $c->claveMonitor => $linea($c->ofertaUno, $c->monitor->id_product),
        ],
    ])->assertSessionHas('success');

    $this->post(route('purchase-quotation-requests.generate-orders', $c->quotationRequest->id_purchase_quotation_request))
        ->assertSessionHas('success');

    $orders = \App\Models\PurchaseOrder::queryAllBranches()->with('details', 'expenses')->get();
    expect($orders)->toHaveCount(3)
        ->and($orders->pluck('status')->unique()->all())->toBe(['draft']);

    $orden = fn ($oferta, $branch) => $orders->first(fn ($o) => $o->id_purchase_quotation === $oferta->id_purchase_quotation && $o->id_branch === $branch->id_branch);

    // Proveedor Uno: una orden a la sucursal B con los 3 monitores
    $unoB = $orden($c->ofertaUno, $e->sucursalB);
    expect($unoB->id_warehouse)->toBe($e->bodegaB->id_warehouse)
        ->and($unoB->details->pluck('id_product')->all())->toBe([$c->monitor->id_product])
        ->and((float) $unoB->details->first()->quantity)->toBe(3.0);

    // Proveedor Dos: una orden por sucursal con las 2 unidades de cada una y el flete repartido
    $dosA = $orden($c->ofertaDos, $e->sucursalA);
    $dosB = $orden($c->ofertaDos, $e->sucursalB);
    expect((float) $dosA->details->first()->quantity)->toBe(2.0)
        ->and((float) $dosB->details->first()->quantity)->toBe(2.0)
        ->and((float) $dosA->details->first()->unit_price)->toBe(8.0)
        ->and((float) $dosA->additional_expenses + (float) $dosB->additional_expenses)->toBe(25.0)
        ->and((float) $dosA->additional_expenses)->toBe(12.5);

    // No se generan dos veces
    $this->post(route('purchase-quotation-requests.generate-orders', $c->quotationRequest->id_purchase_quotation_request))
        ->assertSessionHas('error', 'Las órdenes de compra de esta solicitud ya se generaron.');
    expect(\App\Models\PurchaseOrder::queryAllBranches()->count())->toBe(3);

    // El detalle de la solicitud de cotización lista las órdenes generadas
    $this->get(route('purchase-quotation-requests.show', $c->quotationRequest->id_purchase_quotation_request))
        ->assertOk()->assertSee($dosA->purchase_order_code)->assertDontSee('Generar Órdenes de Compra');

    // Compras ve las órdenes de todas las sucursales; la sucursal A, solo la suya
    $comprasOrdenes = flujoUsuario($e->matriz, ['purchase_orders.ver', 'purchase_orders.editar']);
    $visibles = fn (User $user) => $this->actingAs($user)->get(route('purchase_orders.index'))->assertOk()->viewData('purchase_orders')->pluck('id_purchase_order');
    expect($visibles($comprasOrdenes)->sort()->values()->all())->toBe($orders->pluck('id_purchase_order')->sort()->values()->all());

    $sucursalOrdenes = flujoUsuario($e->sucursalA, ['purchase_orders.ver']);
    expect($visibles($sucursalOrdenes)->all())->toBe([$dosA->id_purchase_order]);

    // Al editar, la orden conserva su cotización de origen
    $this->actingAs($comprasOrdenes)->put(route('purchase_orders.update', $dosB), [
        'id_supplier' => $dosB->id_supplier, 'id_branch' => $dosB->id_branch, 'id_warehouse' => $dosB->id_warehouse,
        'order_date' => now()->format('Y-m-d H:i'), 'expected_date' => now()->addWeek()->format('Y-m-d H:i'),
        'currency' => 'USD', 'payment_terms' => 'Contado',
        'products' => [['id_product' => $e->product->id_product, 'quantity' => 2, 'id_unit' => $e->unit->id_unit, 'unit_price' => 8, 'discount' => 0, 'tax_rate' => 13]],
    ])->assertSessionHasNoErrors();
    expect($dosB->fresh()->id_purchase_quotation)->toBe($c->ofertaDos->id_purchase_quotation)
        ->and($dosB->fresh()->payment_terms)->toBe('Contado');
});
