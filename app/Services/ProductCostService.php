<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\Retaceo;

/**
 * Último costo de los productos.
 *
 * Sin inventario no hay existencias para promediar, así que cada producto guarda el costo de
 * su compra más reciente, por la unidad en que se compró:
 * - Al completar una compra: precio con descuento, sin IVA, más su parte de los gastos de la
 *   orden que forman parte del costo (is_costable).
 * - Al aplicar el retaceo de la compra: el costo puesto en bodega del retaceo, que reemplaza
 *   al de la factura.
 *
 * El costo queda también en cada línea de la compra (unit_cost).
 */
class ProductCostService
{
    public function actualizarDesdeCompra(Purchase $purchase): void
    {
        $purchase->load('details');

        $retaceo = Retaceo::queryAllBranches()
            ->with('details')
            ->where('id_purchase', $purchase->id_purchase)
            ->where('status', 'applied')
            ->first();

        if (! $retaceo && $purchase->status !== 'completed') {
            return;
        }

        $costos = $retaceo ? $this->costosDelRetaceo($purchase, $retaceo) : $this->costosDeLaFactura($purchase);
        $fecha  = $purchase->purchase_date ?? $purchase->created_at;

        foreach ($purchase->details as $detail) {
            if (! isset($costos[$detail->id_purchase_detail])) {
                continue;
            }

            $costo = $costos[$detail->id_purchase_detail];
            $detail->update(['unit_cost' => $costo]);

            // Solo si es la compra más reciente del producto; con la misma fecha gana la última
            // en registrarse (así el retaceo reemplaza el costo de la factura de su compra)
            Product::whereKey($detail->id_product)
                ->where(fn ($q) => $q->whereNull('last_cost_date')->orWhere('last_cost_date', '<=', $fecha))
                ->update([
                    'last_cost'                    => $costo,
                    'last_cost_date'               => $fecha,
                    'id_last_cost_purchase_detail' => $detail->id_purchase_detail,
                ]);
        }
    }

    /**
     * Parte de los gastos de la orden que forman parte del costo y le toca a la compra: se
     * reparten según el valor de lo recibido frente al valor de toda la orden.
     */
    public function gastosDeLaCompra(Purchase $purchase): float
    {
        $purchase->loadMissing('details');

        return round(array_sum($this->gastosPorLinea($purchase)), 4);
    }

    /** @return array<int, float> costo unitario por id_purchase_detail */
    private function costosDeLaFactura(Purchase $purchase): array
    {
        $gastos = $this->gastosPorLinea($purchase);
        $costos = [];

        foreach ($purchase->details as $detail) {
            $cantidad = (float) $detail->quantity_received;
            if ($cantidad <= 0) {
                continue;
            }

            $neto = max(0, (float) $detail->subtotal - (float) $detail->discount);
            $costos[$detail->id_purchase_detail] = round(($neto + ($gastos[$detail->id_purchase_detail] ?? 0)) / $cantidad, 4);
        }

        return $costos;
    }

    /** @return array<int, float> costo unitario por id_purchase_detail */
    private function costosDelRetaceo(Purchase $purchase, Retaceo $retaceo): array
    {
        $costos = [];

        foreach ($retaceo->details as $linea) {
            $detail = $linea->id_purchase_detail
                ? $purchase->details->firstWhere('id_purchase_detail', $linea->id_purchase_detail)
                : $purchase->details->firstWhere('id_product', $linea->id_product);

            if ($detail) {
                $costos[$detail->id_purchase_detail] = round((float) $linea->unit_cost, 4);
            }
        }

        return $costos;
    }

    /** @return array<int, float> gastos de la orden que le tocan a cada línea de la compra */
    private function gastosPorLinea(Purchase $purchase): array
    {
        $order = $purchase->id_purchase_order
            ? PurchaseOrder::queryAllBranches()->find($purchase->id_purchase_order)
            : null;

        if (! $order) {
            return [];
        }

        $gastos    = (float) $order->expenses()->where('is_costable', true)->sum('amount');
        $baseOrden = max(0, (float) $order->subtotal - (float) $order->discount);

        if ($gastos <= 0 || $baseOrden <= 0) {
            return [];
        }

        $porLinea = [];
        foreach ($purchase->details as $detail) {
            $neto = max(0, (float) $detail->subtotal - (float) $detail->discount);
            $porLinea[$detail->id_purchase_detail] = $gastos * $neto / $baseOrden;
        }

        return $porLinea;
    }
}
