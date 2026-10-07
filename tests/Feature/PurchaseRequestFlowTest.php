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
// Flujo: la sucursal crea y envía; el departamento de compras (Casa Matriz) devuelve,
// rechaza o genera la cotización. Una vez enviada, nadie la modifica.
// =============================================================================

const PERMISOS_SUCURSAL_SOLICITUDES = [
    'purchase_requests.ver', 'purchase_requests.crear', 'purchase_requests.editar',
    'purchase_requests.eliminar', 'purchase_requests.enviar',
];

const PERMISOS_COMPRAS_SOLICITUDES = [
    'purchase_requests.ver', 'purchase_requests.crear', 'purchase_requests.editar',
    'purchase_requests.eliminar', 'purchase_requests.enviar', 'purchase_requests.devolver',
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

test('compras genera la cotización de una solicitud de otra sucursal y la sigue viendo', function () {
    $e = flujoEscenario();
    $request = flujoSolicitud($e, $e->sucursalA, $e->bodegaA, $e->usuarioA, 'sent');
    $detail = $request->details()->first();

    $this->actingAs($e->compras)->post(route('purchase-quotation-requests.store'), [
        'id_purchase_request' => $request->id_purchase_request,
        'items' => [['id_purchase_request_detail' => $detail->id_purchase_request_detail, 'quantity' => 2]],
    ])->assertSessionHasNoErrors();

    expect($request->fresh()->status)->toBe('quoted');

    $quotationRequests = $this->get(route('purchase-quotation-requests.index'))->assertOk()->viewData('quotationRequests');
    expect($quotationRequests->pluck('id_purchase_request'))->toContain($request->id_purchase_request);

    // La sucursal ve su solicitud en cotización, pero no la solicitud de otra sucursal
    expect(flujoVisibles($e->usuarioB))->not->toContain($request->id_purchase_request);
    expect(PurchaseQuotationRequest::queryAllBranches()->where('id_purchase_request', $request->id_purchase_request)->count())->toBe(1);
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
