<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseCategory;
use Illuminate\Support\Str;

// El código de ubicación (pasillo-rack-nivel-posición) es único por bodega: cada bodega
// puede tener su propia A-1-1-1.

function ubicacionesEscenario(): object
{
    $tag = Str::upper(Str::random(5));
    $category = WarehouseCategory::create(['name' => "Categoría {$tag}"]);
    $company = Company::create(['name' => "Empresa {$tag}"]);
    $branch = Branch::create(['id_company' => $company->id_company, 'name' => "Sucursal {$tag}"]);
    $bodega = fn (string $name) => Warehouse::create([
        'id_branch' => $branch->id_branch, 'id_warehouse_category' => $category->id_warehouse_category, 'name' => "{$name} {$tag}",
    ]);

    $admin = User::factory()->create();
    $admin->roles()->attach(Role::firstOrCreate(['name' => 'admin'])->id_role, ['assigned_at' => now()]);

    return (object) ['admin' => $admin, 'bodegaA' => $bodega('Bodega A'), 'bodegaB' => $bodega('Bodega B')];
}

function generarUbicaciones(Warehouse $warehouse, array $extra = [])
{
    return test()->post(route('locations.batch-store'), array_merge([
        'id_warehouse' => $warehouse->id_warehouse,
        'pasillo_hasta' => 'B', 'rack_hasta' => 2, 'level_hasta' => 1, 'position_hasta' => 2,
        'capacity' => 10,
    ], $extra));
}

test('la generación masiva crea los mismos códigos en cada bodega y omite los que ya existen', function () {
    $e = ubicacionesEscenario();
    $this->actingAs($e->admin);

    // 2 pasillos x 2 racks x 1 nivel x 2 posiciones = 8
    generarUbicaciones($e->bodegaA)->assertSessionHas('success', 'Se generaron exitosamente 8 ubicaciones masivas.');
    generarUbicaciones($e->bodegaB)->assertSessionHas('success', 'Se generaron exitosamente 8 ubicaciones masivas.');

    expect(Location::where('id_warehouse', $e->bodegaB->id_warehouse)->where('code', 'A-1-1-1')->exists())->toBeTrue();

    // Repetir en la misma bodega solo agrega lo nuevo (pasillo C)
    generarUbicaciones($e->bodegaA, ['pasillo_hasta' => 'C'])
        ->assertSessionHas('success', 'Se generaron exitosamente 4 ubicaciones masivas. (8 ubicaciones ya existían y se omitieron).');
    expect(Location::where('id_warehouse', $e->bodegaA->id_warehouse)->count())->toBe(12);
});

test('la generación masiva tiene un tope por vez', function () {
    $e = ubicacionesEscenario();
    $this->actingAs($e->admin);

    // 26 pasillos x 10 x 10 x 10 = 26000
    generarUbicaciones($e->bodegaA, ['pasillo_hasta' => 'Z', 'rack_hasta' => 10, 'level_hasta' => 10, 'position_hasta' => 10])
        ->assertSessionHas('error');

    expect(Location::where('id_warehouse', $e->bodegaA->id_warehouse)->count())->toBe(0);
});

test('al registrar una ubicación, el código solo no puede repetirse dentro de la misma bodega', function () {
    $e = ubicacionesEscenario();
    $this->actingAs($e->admin);

    $datos = fn (Warehouse $warehouse) => ['id_warehouse' => $warehouse->id_warehouse, 'code' => 'A-1-1-1', 'capacity' => 5, 'is_active' => 1];

    $this->post(route('locations.store'), $datos($e->bodegaA))->assertSessionHasNoErrors();
    $this->post(route('locations.store'), $datos($e->bodegaB))->assertSessionHasNoErrors();
    $this->post(route('locations.store'), $datos($e->bodegaA))->assertSessionHasErrors('code');

    // Al editar, tampoco puede tomar el código de otra ubicación de su bodega
    $otra = Location::create(['id_warehouse' => $e->bodegaA->id_warehouse, 'code' => 'B-1-1-1', 'capacity' => 5]);
    $this->put(route('locations.update', $otra), $datos($e->bodegaA))->assertSessionHasErrors('code');
    $this->put(route('locations.update', $otra), array_merge($datos($e->bodegaA), ['code' => 'B-1-1-1', 'capacity' => 7]))->assertSessionHasNoErrors();
});
