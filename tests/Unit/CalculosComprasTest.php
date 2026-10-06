<?php

use App\Services\PurchaseService;
use App\Services\RetaceoService;

// =============================================================================
// PurchaseService::calcularTotales
// =============================================================================

test('los totales de una compra suman subtotal, descuento e IVA de cada línea', function () {
    $totales = (new PurchaseService)->calcularTotales([
        // 10 x 20 = 200, descuento 20 -> base 180, IVA 13% = 23.40
        ['quantity_received' => 10, 'unit_price' => 20, 'discount' => 20, 'tax_rate' => 13],
        // 3 x 15.50 = 46.50, sin descuento ni IVA
        ['quantity_received' => 3, 'unit_price' => 15.50],
    ]);

    expect($totales)->toBe([
        'subtotal' => 246.5,
        'discount' => 20.0,
        'tax'      => 23.4,
        'total'    => 249.9,
    ]);
});

test('el IVA se calcula sobre la base después del descuento', function () {
    $totales = (new PurchaseService)->calcularTotales([
        ['quantity_received' => 1, 'unit_price' => 100, 'discount' => 100, 'tax_rate' => 13],
    ]);

    expect($totales['tax'])->toBe(0.0)
        ->and($totales['total'])->toBe(0.0);
});

test('una compra sin líneas tiene totales en cero', function () {
    expect((new PurchaseService)->calcularTotales([]))
        ->toBe(['subtotal' => 0.0, 'discount' => 0.0, 'tax' => 0.0, 'total' => 0.0]);
});

// =============================================================================
// RetaceoService::calcularProrrateo
// =============================================================================

test('el flete y los gastos se reparten en proporción al valor FOB de cada línea', function () {
    $resultado = (new RetaceoService)->calcularProrrateo([
        ['id_product' => 1, 'quantity' => 10, 'cost_fob' => 750],  // 75 % del FOB
        ['id_product' => 2, 'quantity' => 5, 'cost_fob' => 250],   // 25 % del FOB
    ], totalFreight: 100, totalExpenses: 40);

    [$a, $b] = $resultado['items'];

    expect($a['freight_amount'])->toBe(75.0)
        ->and($a['expense_amount'])->toBe(30.0)
        ->and($a['total_cost'])->toBe(855.0)
        ->and($a['unit_cost'])->toBe(85.5)
        ->and($b['freight_amount'])->toBe(25.0)
        ->and($b['expense_amount'])->toBe(10.0)
        ->and($b['total_cost'])->toBe(285.0)
        ->and($b['unit_cost'])->toBe(57.0);

    expect($resultado['totals'])->toBe([
        'total_fob'      => 1000.0,
        'total_freight'  => 100.0,
        'total_expenses' => 40.0,
        'total_dai'      => 0.0,
        'total_cost'     => 1140.0,
    ]);
});

test('el DAI se suma al costo de su propia línea', function () {
    $resultado = (new RetaceoService)->calcularProrrateo([
        ['id_product' => 1, 'quantity' => 2, 'cost_fob' => 100, 'dai_amount' => 15],
        ['id_product' => 2, 'quantity' => 1, 'cost_fob' => 100],
    ], totalFreight: 0, totalExpenses: 0);

    expect($resultado['items'][0]['total_cost'])->toBe(115.0)
        ->and($resultado['items'][0]['unit_cost'])->toBe(57.5)
        ->and($resultado['items'][1]['total_cost'])->toBe(100.0)
        ->and($resultado['totals']['total_dai'])->toBe(15.0)
        ->and($resultado['totals']['total_cost'])->toBe(215.0);
});

test('sin valor FOB el flete se reparte en partes iguales', function () {
    $resultado = (new RetaceoService)->calcularProrrateo([
        ['id_product' => 1, 'quantity' => 1, 'cost_fob' => 0],
        ['id_product' => 2, 'quantity' => 1, 'cost_fob' => 0],
    ], totalFreight: 50, totalExpenses: 0);

    expect(array_column($resultado['items'], 'freight_amount'))->toBe([25.0, 25.0]);
});

test('el reparto no pierde ni agrega centavos por redondeo', function () {
    // 100 entre tres líneas iguales: 33.3333 + 33.3333 + 33.3334
    $resultado = (new RetaceoService)->calcularProrrateo([
        ['id_product' => 1, 'quantity' => 1, 'cost_fob' => 10],
        ['id_product' => 2, 'quantity' => 1, 'cost_fob' => 10],
        ['id_product' => 3, 'quantity' => 1, 'cost_fob' => 10],
    ], totalFreight: 100, totalExpenses: 10);

    $items = $resultado['items'];

    expect(round(array_sum(array_column($items, 'freight_amount')), 4))->toBe(100.0)
        ->and(round(array_sum(array_column($items, 'expense_amount')), 4))->toBe(10.0)
        ->and(round(array_sum(array_column($items, 'total_cost')), 4))->toBe($resultado['totals']['total_cost']);
});
