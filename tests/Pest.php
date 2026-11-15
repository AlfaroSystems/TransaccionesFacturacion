<?php

use App\Models\InventoryLocation;
use App\Models\InventoryLocationDetail;
use App\Models\InventoryMovement;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

// Los tests de Feature usan la base *_testing (ver phpunit.xml): RefreshDatabase la migra
// al iniciar la corrida y revierte cada test en una transacción.
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Ubicación de inventario de una ubicación de bodega (se crea con ella).
 */
function ubicacionInventario(Location $location): int
{
    return (int) InventoryLocation::where('id_location', $location->id_location)->value('id_inventory_location');
}

/**
 * Existencia por lote y ubicación de inventario según inventory_location_details, y según la
 * suma de los movimientos: deben coincidir siempre.
 *
 * @return array{0: array<string, float>, 1: array<string, float>}
 */
function existenciaPorUbicacion(): array
{
    $detalle = InventoryLocationDetail::queryAllBranches()
        ->orderBy('id_inventory_lot')->orderBy('id_inventory_location')->get()
        ->mapWithKeys(fn ($d) => [$d->id_inventory_lot.'|'.$d->id_inventory_location => (float) $d->quantity])
        ->all();

    $movimientos = InventoryMovement::queryAllBranches()
        ->whereNotNull('id_inventory_location')
        ->groupBy('id_inventory_lot', 'id_inventory_location')
        ->havingRaw('SUM(quantity_in - quantity_out) <> 0')
        ->orderBy('id_inventory_lot')->orderBy('id_inventory_location')
        ->selectRaw('id_inventory_lot, id_inventory_location, SUM(quantity_in - quantity_out) AS stock')
        ->get()
        ->mapWithKeys(fn ($m) => [$m->id_inventory_lot.'|'.$m->id_inventory_location => (float) $m->stock])
        ->all();

    return [$detalle, $movimientos];
}
