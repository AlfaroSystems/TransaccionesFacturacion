<?php

namespace App\Support;

/**
 * Monedas de las transacciones. USD es la predeterminada y se puede elegir otra en órdenes,
 * compras y cotizaciones.
 *
 * No hay tipo de cambio: los costos de las compras, los retaceos y el costo promedio del
 * inventario suman los montos tal como están, así que una compra en otra moneda se suma como
 * si fueran dólares. Para usarlas en serio habría que agregar el tipo de cambio.
 */
final class Moneda
{
    public const PREDETERMINADA = 'USD';

    /** Código => etiqueta de los selectores */
    public const OPCIONES = [
        'USD' => 'USD ($)',
        'NIO' => 'NIO (C$)',
        'EUR' => 'EUR (€)',
        'CRC' => 'CRC (₡)',
    ];

    public const MENSAJE = 'Elija una de las monedas disponibles.';

    /**
     * Regla del campo de moneda de los formularios: vacía (queda la predeterminada) o una de
     * las disponibles.
     */
    public static function regla(): array
    {
        return ['nullable', 'in:'.implode(',', array_keys(self::OPCIONES))];
    }
}
