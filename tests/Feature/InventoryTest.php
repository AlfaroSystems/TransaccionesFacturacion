<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\InventoryCostAverage;
use App\Models\InventoryLabelPrint;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\Location;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\Retaceo;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseCategory;
use App\Services\InventoryService;
use App\Services\RetaceoService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

// =============================================================================
// Inventario: recibir la compra no mueve existencias. Al aplicar su retaceo, cada línea se
// vuelve un lote por ubicar con su costo final; se reparte en ubicaciones válidas de su bodega
// y al confirmar la colocación entra al inventario. Después se mueve con cambios de ubicación,
// y el kardex lleva el saldo y el costo promedio.
// =============================================================================

function inventarioEscenario(): object
{
    $tag = Str::upper(Str::random(5));
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::firstOrCreate(['name' => 'admin'])->id_role, ['assigned_at' => now()]);

    $company = Company::create(['name' => "Empresa {$tag}"]);
    $matriz = Branch::create(['id_company' => $company->id_company, 'name' => "Casa Matriz {$tag}", 'is_purchasing_department' => true]);
    $sucursal = Branch::create(['id_company' => $company->id_company, 'name' => "Sucursal {$tag}"]);
    $category = WarehouseCategory::create(['name' => "Categoría {$tag}"]);
    $bodega = fn (Branch $branch, string $name) => Warehouse::create(['id_branch' => $branch->id_branch, 'id_warehouse_category' => $category->id_warehouse_category, 'name' => "{$name} {$tag}"]);
    $ubicacion = fn (Warehouse $warehouse, string $code, int $capacity = 100) => Location::create(['id_warehouse' => $warehouse->id_warehouse, 'code' => $code, 'capacity' => $capacity]);

    $bodegaMatriz = $bodega($matriz, 'Bodega Central');
    $bodegaSucursal = $bodega($sucursal, 'Bodega Sucursal');
    $unit = Unit::create(['name' => "Unidad {$tag}", 'abbreviation' => 'UND']);
    $supplier = Supplier::create(['name' => "Proveedor {$tag}", 'email' => Str::lower($tag).'@example.com', 'country' => 'El Salvador', 'is_active' => true]);

    return (object) [
        'admin' => $admin, 'company' => $company, 'matriz' => $matriz, 'sucursal' => $sucursal,
        'bodegaMatriz' => $bodegaMatriz, 'bodegaSucursal' => $bodegaSucursal,
        'a1' => $ubicacion($bodegaMatriz, 'A1'), 'a2' => $ubicacion($bodegaMatriz, 'A2'), 'c1' => $ubicacion($bodegaMatriz, 'C1', 5),
        's1' => $ubicacion($bodegaSucursal, 'S1'),
        'unit' => $unit, 'supplier' => $supplier,
        'laptop' => Product::create(['name' => "Laptop {$tag}", 'sku' => 'L-'.$tag]),
        'monitor' => Product::create(['name' => "Monitor {$tag}", 'sku' => 'M-'.$tag]),
    ];
}

/** Orden emitida para la bodega indicada; $lineas: [[producto, cantidad, precio], ...] */
function inventarioOrden(object $e, Warehouse $warehouse, array $lineas): object
{
    $order = PurchaseOrder::create([
        'id_supplier' => $e->supplier->id_supplier, 'id_branch' => $warehouse->id_branch, 'id_warehouse' => $warehouse->id_warehouse,
        'id_user' => $e->admin->id_user, 'order_date' => now(), 'expected_date' => now()->addWeek(),
        'currency' => 'USD', 'payment_terms' => 'Contado', 'status' => 'issued',
    ]);

    $detalles = array_map(fn ($l) => PurchaseOrderDetail::create([
        'id_purchase_order' => $order->id_purchase_order, 'id_product' => $l[0]->id_product,
        'quantity' => $l[1], 'id_unit' => $e->unit->id_unit, 'unit_price' => $l[2],
    ]), $lineas);

    return (object) ['order' => $order, 'lineas' => $detalles];
}

/** Datos de la compra; $lineas: [[línea de orden, cantidad, precio], ...] */
function inventarioCompra(object $e, object $orden, array $lineas, string $status = 'received'): array
{
    return [
        'id_purchase_order' => $orden->order->id_purchase_order,
        'id_supplier' => $e->supplier->id_supplier,
        'purchase_date' => now()->toDateString(),
        'status' => $status,
        'details' => array_map(fn ($l) => [
            'id_purchase_order_detail' => $l[0]->id_purchase_order_detail,
            'id_product' => $l[0]->id_product,
            'quantity_received' => $l[1],
            'unit_price' => $l[2],
        ], $lineas),
    ];
}

/** Aplica a la compra un retaceo con el costo unitario de cada línea, en su orden */
function inventarioRetaceo(object $e, Purchase $compra, array $costos): Retaceo
{
    $compra->load(['details' => fn ($q) => $q->orderBy('id_purchase_detail')]);
    $retaceo = Retaceo::queryAllBranches()->create([
        'id_supplier' => $e->supplier->id_supplier, 'id_purchase' => $compra->id_purchase,
        'retaceo_date' => now(), 'status' => 'calculated',
    ]);

    foreach ($compra->details->values() as $i => $detail) {
        $retaceo->details()->create([
            'id_purchase_detail' => $detail->id_purchase_detail, 'id_product' => $detail->id_product,
            'quantity' => $detail->quantity_received, 'unit_cost' => $costos[$i], 'total_cost' => $detail->quantity_received * $costos[$i],
        ]);
    }

    app(RetaceoService::class)->cambiarEstado($retaceo, 'applied');

    return $retaceo->fresh();
}

/** Registra la compra y aplica su retaceo; devuelve sus lotes por ubicar */
function inventarioLotes(object $e, object $orden, array $lineas, array $costos): Collection
{
    test()->post(route('purchases.store'), inventarioCompra($e, $orden, $lineas))->assertSessionHasNoErrors();
    $compra = Purchase::queryAllBranches()->latest('id_purchase')->first();
    inventarioRetaceo($e, $compra, $costos);

    return InventoryLot::queryAllBranches()
        ->whereIn('id_purchase_detail', $compra->details()->pluck('id_purchase_detail'))
        ->where('status', '!=', InventoryLot::STATUS_CANCELLED)
        ->orderBy('id_inventory_lot')
        ->get();
}

/** Envía el reparto del lote; $partes: [[ubicación, cantidad], ...] */
function inventarioUbicar(InventoryLot $lot, array $partes, bool $confirmar = false)
{
    return test()->put(route('inventory.lots.placements', $lot), [
        'placements' => array_map(fn ($p) => ['id_location' => $p[0]->id_location, 'quantity' => $p[1]], $partes),
        ...($confirmar ? ['confirmar' => 1] : []),
    ]);
}

function inventarioPromedio(object $e, Product $product): ?InventoryCostAverage
{
    return InventoryCostAverage::queryAllBranches()
        ->where('id_company', $e->company->id_company)
        ->where('id_product', $product->id_product)
        ->first();
}

/** Usuario de la sucursal con los permisos indicados */
function inventarioUsuario(Branch $branch, array $permisos): User
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

test('recibir la compra no mueve el inventario: los lotes nacen por ubicar al aplicar el retaceo', function () {
    $e = inventarioEscenario();
    $this->actingAs($e->admin);
    $orden = inventarioOrden($e, $e->bodegaMatriz, [[$e->laptop, 10, 10], [$e->monitor, 5, 20]]);

    $this->post(route('purchases.store'), inventarioCompra($e, $orden, [[$orden->lineas[0], 10, 10], [$orden->lineas[1], 5, 20]]))
        ->assertSessionHasNoErrors();
    $compra = Purchase::queryAllBranches()->sole();

    expect(InventoryLot::queryAllBranches()->count())->toBe(0)
        ->and(InventoryMovement::queryAllBranches()->count())->toBe(0);
    $this->get(route('purchases.show', $compra))->assertOk()->assertSee('sus lotes se crean al aplicar el retaceo de esta compra');

    // El retaceo fija el costo puesto en bodega: los lotes nacen con él, por ubicar y sin existencia
    inventarioRetaceo($e, $compra, [12, 22]);

    $lotes = InventoryLot::queryAllBranches()->orderBy('id_inventory_lot')->get();
    expect($lotes)->toHaveCount(2)
        ->and($lotes->pluck('status')->unique()->all())->toBe([InventoryLot::STATUS_PENDING])
        ->and($lotes[0]->lot_code)->toBe('LOT-'.now()->format('Ymd').'-001')
        ->and($lotes[1]->lot_code)->toBe('LOT-'.now()->format('Ymd').'-002')
        ->and($lotes[0]->id_warehouse)->toBe($e->bodegaMatriz->id_warehouse)
        ->and((float) $lotes[0]->unit_cost)->toBe(12.0)
        ->and((float) $lotes[1]->total_cost)->toBe(110.0)
        ->and(InventoryMovement::queryAllBranches()->count())->toBe(0)
        ->and(inventarioPromedio($e, $e->laptop))->toBeNull();

    // Repetirlo no duplica los lotes
    app(InventoryService::class)->crearLotes($compra);
    expect(InventoryLot::queryAllBranches()->count())->toBe(2);

    $this->get(route('inventory.index'))->assertOk()->assertSee('2 lotes esperan su ubicaci');
    $this->get(route('inventory.lots.index'))->assertOk()->assertSee($lotes[0]->lot_code)->assertSee('Ubicar');
    $this->get(route('purchases.show', $compra))->assertOk()->assertSee($lotes[0]->lot_code)->assertDontSee('sus lotes se crean al aplicar el retaceo de esta compra');
    $this->get(route('inventory.lots.show', $lotes[0]))->assertOk()->assertSee('Confirmar colocación');
});

test('el lote se reparte en ubicaciones válidas y entra al inventario al confirmar su colocación', function () {
    $e = inventarioEscenario();
    $this->actingAs($e->admin);
    $orden = inventarioOrden($e, $e->bodegaMatriz, [[$e->laptop, 10, 10]]);
    $lote = inventarioLotes($e, $orden, [[$orden->lineas[0], 10, 10]], [12])->sole();

    // Ubicación no válida: se vuelve a asignar, con el error en su fila
    inventarioUbicar($lote, [[$e->s1, 10]])->assertSessionHasErrors(['placements.0.id_location' => 'La ubicación debe ser de la bodega del lote.']);
    $e->a2->update(['is_active' => false]);
    inventarioUbicar($lote, [[$e->a1, 4], [$e->a2, 6]])->assertSessionHasErrors(['placements.1.id_location' => 'La ubicación A2 está inactiva.']);
    $e->a2->update(['is_active' => true]);
    inventarioUbicar($lote, [[$e->c1, 6]])->assertSessionHasErrors('placements.0.id_location');
    inventarioUbicar($lote, [[$e->a1, 8], [$e->a2, 4]])->assertSessionHasErrors('placements');
    inventarioUbicar($lote, [[$e->a1, 4], [$e->a1, 6]])->assertSessionHasErrors('placements.1.id_location');
    expect($lote->placements()->count())->toBe(0);

    // Un reparto incompleto se guarda, pero no se confirma
    inventarioUbicar($lote, [[$e->a1, 6]])->assertSessionHasNoErrors()->assertSessionHas('success');
    expect($lote->placements()->count())->toBe(1)->and($lote->fresh()->status)->toBe(InventoryLot::STATUS_PENDING);
    inventarioUbicar($lote, [[$e->a1, 6]], confirmar: true)->assertSessionHasErrors('placements');
    expect(InventoryMovement::queryAllBranches()->count())->toBe(0);

    // Completo: cada ubicación es una entrada y la empresa promedia su costo
    inventarioUbicar($lote, [[$e->a1, 6], [$e->a2, 4]], confirmar: true)->assertSessionHasNoErrors();

    $lote->refresh();
    $entradas = InventoryMovement::queryAllBranches()->orderBy('id_inventory_movement')->get();
    $promedio = inventarioPromedio($e, $e->laptop);

    expect($lote->status)->toBe(InventoryLot::STATUS_ACTIVE)
        ->and($lote->placed_at)->not->toBeNull()
        ->and($entradas->pluck('movement_type')->unique()->all())->toBe([InventoryMovement::TYPE_PURCHASE_RECEIPT])
        ->and($entradas->pluck('id_location')->all())->toBe([$e->a1->id_location, $e->a2->id_location])
        ->and($entradas->pluck('quantity_in')->map(fn ($q) => (float) $q)->all())->toBe([6.0, 4.0])
        ->and((float) $entradas->sum('total_cost'))->toBe(120.0)
        ->and((float) $promedio->quantity_on_hand)->toBe(10.0)
        ->and((float) $promedio->average_unit_cost)->toBe(12.0);

    $this->get(route('inventory.index', ['search' => $e->laptop->sku]))->assertOk()->assertSee('A1')->assertSee('A2')->assertSee('$120.00');

    // Ya colocado no se edita su reparto
    inventarioUbicar($lote, [[$e->a1, 10]])->assertSessionHas('error', 'Solo se reparte un lote por ubicar.');

    // Sin el permiso no se ubica
    $this->actingAs(inventarioUsuario($e->matriz, ['inventory.ver']));
    inventarioUbicar($lote, [[$e->a1, 10]])->assertForbidden();
});

test('revertir el retaceo anula los lotes por ubicar; colocado uno, ya no se revierte', function () {
    $e = inventarioEscenario();
    $this->actingAs($e->admin);
    $orden = inventarioOrden($e, $e->bodegaMatriz, [[$e->laptop, 10, 10]]);
    $lote = inventarioLotes($e, $orden, [[$orden->lineas[0], 10, 10]], [12])->sole();
    $retaceo = Retaceo::queryAllBranches()->sole();

    $this->patch(route('retaceos.updateStatus', $retaceo), ['status' => 'draft'])->assertSessionHas('success');
    expect($retaceo->fresh()->status)->toBe('draft')->and($lote->fresh()->status)->toBe(InventoryLot::STATUS_CANCELLED);

    // Corregido, se aplica de nuevo: un lote nuevo, con el costo nuevo
    $retaceo->details()->update(['unit_cost' => 13, 'total_cost' => 130]);
    app(RetaceoService::class)->cambiarEstado($retaceo->fresh(), 'calculated');
    app(RetaceoService::class)->cambiarEstado($retaceo->fresh(), 'applied');

    $nuevo = InventoryLot::queryAllBranches()->where('status', InventoryLot::STATUS_PENDING)->sole();
    expect($nuevo->lot_code)->toBe('LOT-'.now()->format('Ymd').'-002')->and((float) $nuevo->unit_cost)->toBe(13.0);
    $this->get(route('retaceos.show', $retaceo))->assertOk()->assertSee('Revertir a borrador')->assertSee($nuevo->lot_code);

    inventarioUbicar($nuevo, [[$e->a1, 10]], confirmar: true)->assertSessionHasNoErrors();

    $this->patch(route('retaceos.updateStatus', $retaceo), ['status' => 'draft'])
        ->assertSessionHas('error', "No se puede revertir el retaceo: el lote {$nuevo->lot_code} ya se colocó y entró al inventario.");
    expect($retaceo->fresh()->status)->toBe('applied');
    $this->get(route('retaceos.show', $retaceo))->assertOk()->assertDontSee('Revertir a borrador');

    // Y su compra no se anula
    $compra = Purchase::queryAllBranches()->sole();
    $this->patch(route('purchases.updateStatus', $compra), ['status' => 'cancelled'])->assertSessionHas('error');
    expect($compra->fresh()->status)->toBe('received');
});

test('mientras está por ubicar, el lote se divide y anota el lote del proveedor y su vencimiento', function () {
    $e = inventarioEscenario();
    $this->actingAs($e->admin);
    $orden = inventarioOrden($e, $e->bodegaMatriz, [[$e->laptop, 10, 10]]);
    $lote = inventarioLotes($e, $orden, [[$orden->lineas[0], 10, 10]], [12.3333])->sole();

    $this->put(route('inventory.lots.update', $lote), ['supplier_lot_number' => 'FAB-01', 'expiration_date' => '2027-12-31'])
        ->assertSessionHas('success');
    expect($lote->fresh()->supplier_lot_number)->toBe('FAB-01')
        ->and($lote->fresh()->expiration_date->format('Y-m-d'))->toBe('2027-12-31');

    // El reparto guardado era para el lote completo
    inventarioUbicar($lote, [[$e->a1, 10]])->assertSessionHasNoErrors();

    // Las partes deben ser al menos dos, mayores que cero y sumar todo el lote
    $dividir = fn (array $partes) => $this->post(route('inventory.lots.split', $lote), ['parts' => $partes]);
    $dividir([['quantity' => 6], ['quantity' => 3]])->assertSessionHasErrors(['parts' => 'Las partes suman 9 y el lote tiene 10: deben sumar todo el lote.']);
    $dividir([['quantity' => 10]])->assertSessionHasErrors('parts');
    $dividir([['quantity' => 10], ['quantity' => 0]])->assertSessionHasErrors('parts.1.quantity');

    $dividir([
        ['quantity' => 7, 'supplier_lot_number' => 'FAB-01', 'expiration_date' => '2027-12-31'],
        ['quantity' => 3, 'supplier_lot_number' => 'FAB-02'],
    ])->assertSessionHasNoErrors()->assertSessionHas('success');

    $lotes = InventoryLot::queryAllBranches()->orderBy('id_inventory_lot')->get();
    expect($lotes)->toHaveCount(2)
        ->and($lotes[0]->id_inventory_lot)->toBe($lote->id_inventory_lot)
        ->and($lotes->pluck('quantity_received')->map(fn ($q) => (float) $q)->all())->toBe([7.0, 3.0])
        ->and($lotes[1]->lot_code)->toBe('LOT-'.now()->format('Ymd').'-002')
        ->and($lotes[1]->status)->toBe(InventoryLot::STATUS_PENDING)
        ->and($lotes[1]->id_purchase_detail)->toBe($lote->id_purchase_detail)
        ->and($lotes[1]->id_warehouse)->toBe($lote->id_warehouse)
        ->and((float) $lotes[1]->unit_cost)->toBe(12.3333)
        // El valor no cambia: la última parte toma el redondeo
        ->and(round((float) $lotes->sum('total_cost'), 4))->toBe(123.333)
        ->and($lotes[1]->supplier_lot_number)->toBe('FAB-02')
        ->and($lotes[1]->expiration_date)->toBeNull()
        ->and($lote->placements()->count())->toBe(0);

    // La viñeta lleva el lote del proveedor y el vencimiento
    $this->post(route('inventory.labels.store', $lotes[0]), ['quantity_printed' => 1]);
    $this->get(route('inventory.labels.print', InventoryLabelPrint::sole()))->assertOk()->assertSee('FAB-01')->assertSee('31/12/2027');
    $this->get(route('inventory.lots.index'))->assertOk()->assertSee('Prov. FAB-02');

    // Revertir el retaceo anula las dos partes, y un lote anulado ya no se divide
    app(RetaceoService::class)->cambiarEstado(Retaceo::queryAllBranches()->sole(), 'draft');
    expect(InventoryLot::queryAllBranches()->pluck('status')->unique()->all())->toBe([InventoryLot::STATUS_CANCELLED]);
    $dividir([['quantity' => 5], ['quantity' => 2]])->assertSessionHas('error', 'Solo se divide un lote por ubicar.');
});

test('un lote disponible se cambia de ubicación y el kardex muestra el saldo y el costo promedio', function () {
    $e = inventarioEscenario();
    $this->actingAs($e->admin);
    $orden = inventarioOrden($e, $e->bodegaMatriz, [[$e->laptop, 10, 10]]);

    // 6 laptops a $10 y luego 4 a $13, ambas en A1: el promedio es (60 + 52) / 10
    $primero = inventarioLotes($e, $orden, [[$orden->lineas[0], 6, 10]], [10])->sole();
    inventarioUbicar($primero, [[$e->a1, 6]], confirmar: true)->assertSessionHasNoErrors();
    $segundo = inventarioLotes($e, $orden, [[$orden->lineas[0], 4, 13]], [13])->sole();
    inventarioUbicar($segundo, [[$e->a1, 4]], confirmar: true)->assertSessionHasNoErrors();

    $mover = fn (array $datos) => $this->post(route('inventory.lots.relocate', $primero), $datos + [
        'id_warehouse' => $e->bodegaMatriz->id_warehouse, 'id_source_location' => $e->a1->id_location,
    ]);

    $mover(['id_destination_location' => $e->a1->id_location, 'quantity' => 1])->assertSessionHasErrors('id_destination_location');
    $mover(['id_destination_location' => $e->s1->id_location, 'quantity' => 1])->assertSessionHasErrors('id_destination_location');
    $mover(['id_destination_location' => $e->a2->id_location, 'quantity' => 7])->assertSessionHasErrors(['quantity' => 'Del lote '.$primero->lot_code.' solo hay 6 en esa ubicación.']);
    $mover(['id_destination_location' => $e->a2->id_location, 'quantity' => 2])->assertSessionHasNoErrors()->assertSessionHas('success');

    $existencias = app(InventoryService::class)->existenciasDelLote($primero)->mapWithKeys(fn ($f) => [$f->id_location => (float) $f->stock])->sortKeys();
    expect($existencias->all())->toBe([$e->a1->id_location => 4.0, $e->a2->id_location => 2.0])
        ->and(InventoryMovement::queryAllBranches()->whereIn('movement_type', ['relocation_out', 'relocation_in'])->count())->toBe(2)
        ->and((float) inventarioPromedio($e, $e->laptop)->average_unit_cost)->toBe(11.2);

    // Kardex: el más reciente primero, con el saldo que deja cada movimiento
    $kardex = $this->get(route('inventory.movements', ['search' => $e->laptop->sku]))->assertOk()
        ->assertSee('Cambio de ubicación (entra)')->assertSee('$11.2000')->viewData('movements');
    expect($kardex->getCollection()->map(fn ($m) => (float) $m->running_quantity)->all())->toBe([10.0, 8.0, 10.0, 6.0]);

    // Filtrado por tipo, el saldo sigue contando los movimientos anteriores
    $entradas = $this->get(route('inventory.movements', ['type' => 'purchase_receipt']))->assertOk()->viewData('movements');
    expect($entradas->getCollection()->map(fn ($m) => (float) $m->running_quantity)->all())->toBe([10.0, 6.0]);

    $this->get(route('inventory.lots.show', $primero))->assertOk()->assertSee('Existencia por ubicación')->assertSee('Mover');
});

test('lo comprado entra a Casa Matriz: la sucursal no ve sus lotes ni existencias hasta que se las repartan', function () {
    $e = inventarioEscenario();
    $this->actingAs($e->admin);

    // La orden de la sucursal también entra a la bodega de Casa Matriz (sin elegirla, es la única)
    $ordenMatriz = inventarioOrden($e, $e->bodegaMatriz, [[$e->laptop, 3, 10]]);
    $ordenSucursal = inventarioOrden($e, $e->bodegaSucursal, [[$e->monitor, 2, 20]]);
    $laptops = inventarioLotes($e, $ordenMatriz, [[$ordenMatriz->lineas[0], 3, 10]], [10])->sole();
    $monitores = inventarioLotes($e, $ordenSucursal, [[$ordenSucursal->lineas[0], 2, 20]], [20])->sole();
    inventarioUbicar($laptops, [[$e->a1, 3]], confirmar: true)->assertSessionHasNoErrors();

    expect($monitores->id_warehouse)->toBe($e->bodegaMatriz->id_warehouse);

    $sucursal = inventarioUsuario($e->sucursal, ['inventory.ver']);
    $matriz = inventarioUsuario($e->matriz, ['inventory.ver']);
    $productos = fn (User $user) => $this->actingAs($user)->get(route('inventory.index'))->assertOk()
        ->viewData('stock')->pluck('id_product')->all();
    $lotes = fn (User $user) => $this->actingAs($user)->get(route('inventory.lots.index', ['status' => 'todos']))->assertOk()
        ->viewData('lots')->pluck('id_inventory_lot')->sort()->values()->all();

    expect($productos($sucursal))->toBe([])
        ->and($lotes($sucursal))->toBe([])
        ->and($productos($matriz))->toBe([$e->laptop->id_product])
        ->and($lotes($matriz))->toBe([$laptops->id_inventory_lot, $monitores->id_inventory_lot]);

    $this->actingAs($sucursal)->get(route('inventory.lots.show', $monitores))->assertNotFound();

    // Sin permiso no se ve
    $this->actingAs(inventarioUsuario($e->sucursal, ['purchases.ver']))->get(route('inventory.index'))->assertForbidden();
});

test('Casa Matriz registra la recepción de la orden de una sucursal y ambas ven la compra', function () {
    $e = inventarioEscenario();
    $orden = inventarioOrden($e, $e->bodegaSucursal, [[$e->monitor, 2, 20]]);
    $compras = inventarioUsuario($e->matriz, ['purchases.ver', 'purchases.crear']);
    $this->actingAs($compras);

    // A una bodega de la sucursal no puede entrar
    $this->post(route('purchases.store'), [...inventarioCompra($e, $orden, [[$orden->lineas[0], 2, 20]]), 'id_warehouse' => $e->bodegaSucursal->id_warehouse])
        ->assertSessionHasErrors(['id_warehouse' => 'La mercadería entra a una bodega de Casa Matriz (el departamento de compras), que luego la reparte a las sucursales.']);

    $this->post(route('purchases.store'), [...inventarioCompra($e, $orden, [[$orden->lineas[0], 2, 20]]), 'id_warehouse' => $e->bodegaMatriz->id_warehouse])
        ->assertSessionHasNoErrors();

    $compra = Purchase::queryAllBranches()->sole();
    expect($compra->id_branch)->toBe($e->sucursal->id_branch)
        ->and($compra->id_warehouse)->toBe($e->bodegaMatriz->id_warehouse);

    // La compra es de la sucursal que pidió, pero la ve también el departamento de compras
    $this->get(route('purchases.show', $compra))->assertOk();
    $this->actingAs(inventarioUsuario($e->sucursal, ['purchases.ver']))->get(route('purchases.show', $compra))->assertOk();
});
