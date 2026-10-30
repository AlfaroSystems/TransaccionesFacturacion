<?php

use App\Models\ExpenseType;
use App\Models\Product;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\File;

function adminParaEscapado(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::firstOrCreate(['name' => 'admin'])->id_role, ['assigned_at' => now()]);

    return $user;
}

/**
 * Contenido de todos los <script> de la página.
 */
function scriptsDe(string $html): string
{
    preg_match_all('#<script\b[^>]*>(.*?)</script>#is', $html, $matches);

    return implode("\n", $matches[1]);
}

test('las órdenes de compra no insertan nombres dentro del JavaScript de la página', function () {
    // Ejecuta código si se inserta sin escapar: como HTML (la etiqueta) o dentro de una
    // plantilla de JS (el ${...}, que el escapado de Blade no protege)
    $nombre = 'Taladro ${alert(1)} <img src=x onerror=alert(2)>';
    Product::create(['name' => $nombre, 'sku' => 'SKU-XSS']);
    Unit::create(['name' => $nombre, 'abbreviation' => 'XSS']);
    ExpenseType::create(['name' => $nombre, 'description' => $nombre, 'is_active' => true]);

    $html = $this->actingAs(adminParaEscapado())
        ->get(route('purchase_orders.index'))
        ->assertOk()
        ->getContent();

    // Los nombres llegan escapados como HTML en las opciones de los <template>...
    expect($html)->toContain('Taladro ${alert(1)} &lt;img src=x onerror=alert(2)&gt;')
        // ...y ninguno queda dentro de un <script>
        ->and(scriptsDe($html))->not->toContain('alert(1)')->not->toContain('alert(2)');
});

test('las plantillas de JavaScript escapan los textos que insertan como HTML', function () {
    // ${d.product_name}, ${p.name}, ${data.description ?? ''}...: textos que vienen de la base
    // insertados sin escapeHtml() en el HTML que arman las plantillas con innerHTML
    $patron = '/\$\{\s*\w+\.(?:name|product_name|unit_name|product_code|code|description|full_name|email)\s*(?:(?:\|\||\?\?)\s*(?:\'[^\']*\'|""))?\s*\}/';

    $sinEscapar = collect(File::allFiles(resource_path('views')))
        ->flatMap(fn ($archivo) => collect(file($archivo->getPathname()))
            ->filter(fn ($linea) => preg_match($patron, $linea))
            ->map(fn ($linea, $i) => $archivo->getRelativePathname() . ':' . ($i + 1) . ' ' . trim($linea)))
        ->values()
        ->all();

    expect($sinEscapar)->toBe([]);
});
