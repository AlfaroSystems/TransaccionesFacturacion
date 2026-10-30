<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\InventoryCostAverage;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\Location;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Support\DocumentSequence;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

/**
 * Inventario por lote, bodega y ubicación, y costo promedio por empresa.
 *
 * - Cada línea de una compra recibida (o completada) entra como un lote, con un movimiento
 *   de entrada en la bodega de la compra y la ubicación elegida en su línea.
 * - El costo del lote es el de la compra (ProductCostService): el de la factura o, al
 *   aplicarse el retaceo, su costo puesto en bodega. Si cambia después de la entrada, el
 *   lote toma el nuevo costo y la diferencia se registra como ajuste de costo, que suma o
 *   resta al valor del inventario.
 * - Anular una compra recibida revierte la entrada de sus lotes, si no se han movido.
 * - inventory_cost_averages lleva la existencia, el valor y el costo promedio ponderado de
 *   cada producto por empresa.
 */
class InventoryService
{
    public function __construct(private readonly ProductCostService $costos = new ProductCostService) {}

    /**
     * Lleva el inventario al estado de la compra. Se llama después de cada cambio de la
     * compra o de su retaceo, dentro de la misma transacción; repetirlo no duplica nada.
     */
    public function sincronizarCompra(Purchase $purchase): void
    {
        $purchase = Purchase::queryAllBranches()->with('details')->findOrFail($purchase->getKey());

        $lots = InventoryLot::queryAllBranches()
            ->whereIn('id_purchase_detail', $purchase->details->pluck('id_purchase_detail'))
            ->where('status', InventoryLot::STATUS_ACTIVE)
            ->lockForUpdate()
            ->get();

        if (in_array($purchase->status, Purchase::RECEIVED_STATUSES, true)) {
            $costos = $this->costos->costosDeCompra($purchase);

            $lots->isEmpty()
                ? $this->registrarEntrada($purchase, $costos)
                : $this->ajustarCostos($purchase, $lots, $costos);
        } elseif ($purchase->status === 'cancelled' && $lots->isNotEmpty()) {
            $this->revertirEntrada($purchase, $lots);
        }
    }

    /**
     * @param  array<int, float>  $costos  costo unitario por id_purchase_detail
     */
    private function registrarEntrada(Purchase $purchase, array $costos): void
    {
        $warehouseId = $purchase->id_warehouse
            ?? PurchaseOrder::queryAllBranches()->whereKey($purchase->id_purchase_order)->value('id_warehouse');

        if (! $warehouseId) {
            throw new InvalidArgumentException('La compra no tiene bodega de destino: elíjala para dar entrada a su mercadería.');
        }

        $companyId = $this->empresaDe($purchase);
        $fecha = $purchase->purchase_date ?? now();

        // Ubicaciones elegidas en las líneas que sí son de la bodega de la compra
        $ubicaciones = Location::queryAllBranches()
            ->whereIn('id_location', $purchase->details->pluck('id_location')->filter())
            ->where('id_warehouse', $warehouseId)
            ->pluck('id_location')
            ->all();

        foreach ($purchase->details as $detail) {
            $cantidad = (float) $detail->quantity_received;
            if ($cantidad <= 0) {
                continue;
            }

            $costo = (float) ($costos[$detail->id_purchase_detail] ?? 0);
            $total = round($cantidad * $costo, 4);

            $lot = InventoryLot::create([
                'lot_code' => DocumentSequence::next('LOT-'.now()->year.'-', 'inventory_lots', 'lot_code', 5),
                'id_product' => $detail->id_product,
                'id_purchase_detail' => $detail->id_purchase_detail,
                'received_at' => $fecha,
                'quantity_received' => $cantidad,
                'unit_cost' => $costo,
                'total_cost' => $total,
                'status' => InventoryLot::STATUS_ACTIVE,
            ]);

            $this->registrarMovimiento($lot, [
                'id_warehouse' => $warehouseId,
                'id_location' => in_array($detail->id_location, $ubicaciones) ? $detail->id_location : null,
                'movement_type' => InventoryMovement::TYPE_PURCHASE_RECEIPT,
                'movement_date' => $fecha,
                'quantity_in' => $cantidad,
                'unit_cost' => $costo,
                'total_cost' => $total,
            ], $purchase);

            $this->acumularPromedio($companyId, $detail->id_product, $cantidad, $total);
        }
    }

    /**
     * El lote toma el costo actual de su línea; la diferencia por lo recibido va al valor
     * del inventario. Mientras no haya ventas, todo lo recibido sigue en la empresa.
     *
     * @param  Collection<int, InventoryLot>  $lots
     * @param  array<int, float>  $costos
     */
    private function ajustarCostos(Purchase $purchase, Collection $lots, array $costos): void
    {
        $companyId = $this->empresaDe($purchase);

        foreach ($lots as $lot) {
            if (! isset($costos[$lot->id_purchase_detail])) {
                continue;
            }

            $costo = (float) $costos[$lot->id_purchase_detail];
            if (abs($costo - (float) $lot->unit_cost) < 0.00005) {
                continue;
            }

            $total = round((float) $lot->quantity_received * $costo, 4);
            $diferencia = round($total - (float) $lot->total_cost, 4);
            $entrada = $this->entradaDe($lot);

            $lot->update(['unit_cost' => $costo, 'total_cost' => $total]);

            $this->registrarMovimiento($lot, [
                'id_warehouse' => $entrada->id_warehouse,
                'id_location' => $entrada->id_location,
                'movement_type' => InventoryMovement::TYPE_COST_ADJUSTMENT,
                'movement_date' => now(),
                'unit_cost' => $costo,
                'total_cost' => $diferencia,
            ], $purchase);

            $this->acumularPromedio($companyId, $lot->id_product, 0, $diferencia);
        }
    }

    /**
     * Saca del inventario lo que entró con la compra. Solo si sus lotes no se han movido
     * (su existencia sigue completa donde entró).
     *
     * @param  Collection<int, InventoryLot>  $lots
     */
    private function revertirEntrada(Purchase $purchase, Collection $lots): void
    {
        $propios = [InventoryMovement::TYPE_PURCHASE_RECEIPT, InventoryMovement::TYPE_COST_ADJUSTMENT];

        foreach ($lots as $lot) {
            $movido = InventoryMovement::queryAllBranches()
                ->where('id_inventory_lot', $lot->id_inventory_lot)
                ->whereNotIn('movement_type', $propios)
                ->exists();

            if ($movido) {
                throw new InvalidArgumentException("No se puede anular la compra: el lote {$lot->lot_code} ya tuvo movimientos de inventario.");
            }
        }

        $companyId = $this->empresaDe($purchase);

        foreach ($lots as $lot) {
            $entrada = $this->entradaDe($lot);
            $cantidad = (float) $lot->quantity_received;

            $this->registrarMovimiento($lot, [
                'id_warehouse' => $entrada->id_warehouse,
                'id_location' => $entrada->id_location,
                'movement_type' => InventoryMovement::TYPE_PURCHASE_CANCEL,
                'movement_date' => now(),
                'quantity_out' => $cantidad,
                'unit_cost' => (float) $lot->unit_cost,
                'total_cost' => (float) $lot->total_cost,
            ], $purchase);

            $lot->update(['status' => InventoryLot::STATUS_CANCELLED]);

            $this->acumularPromedio($companyId, $lot->id_product, -$cantidad, -(float) $lot->total_cost);
        }
    }

    private function registrarMovimiento(InventoryLot $lot, array $datos, Purchase $purchase): InventoryMovement
    {
        return InventoryMovement::create($datos + [
            'id_product' => $lot->id_product,
            'id_inventory_lot' => $lot->id_inventory_lot,
            'quantity_in' => 0,
            'quantity_out' => 0,
            'reference_type' => InventoryMovement::REFERENCE_PURCHASE,
            'reference_id' => $purchase->id_purchase,
            'id_user' => Auth::id(),
        ]);
    }

    /**
     * Suma (o resta) cantidad y valor al producto en la empresa y recalcula su costo
     * promedio. Sin existencia, el valor queda en cero y se conserva el último promedio.
     */
    private function acumularPromedio(int $companyId, int $productId, float $cantidad, float $valor): void
    {
        // Se crea si no existe y se bloquea: dos entradas a la vez no deben pisarse
        InventoryCostAverage::queryAllBranches()->insertOrIgnore([
            'id_company' => $companyId,
            'id_product' => $productId,
            'updated_at' => now(),
        ]);

        $promedio = InventoryCostAverage::queryAllBranches()
            ->where('id_company', $companyId)
            ->where('id_product', $productId)
            ->lockForUpdate()
            ->firstOrFail();

        $existencia = round((float) $promedio->quantity_on_hand + $cantidad, 4);
        $valorTotal = $existencia > 0 ? round((float) $promedio->inventory_value + $valor, 4) : 0;

        $promedio->update([
            'quantity_on_hand' => $existencia,
            'inventory_value' => $valorTotal,
            'average_unit_cost' => $existencia > 0 ? round($valorTotal / $existencia, 4) : $promedio->average_unit_cost,
        ]);
    }

    private function entradaDe(InventoryLot $lot): InventoryMovement
    {
        return InventoryMovement::queryAllBranches()
            ->where('id_inventory_lot', $lot->id_inventory_lot)
            ->where('movement_type', InventoryMovement::TYPE_PURCHASE_RECEIPT)
            ->firstOrFail();
    }

    private function empresaDe(Purchase $purchase): int
    {
        $branchId = $purchase->id_branch
            ?? PurchaseOrder::queryAllBranches()->whereKey($purchase->id_purchase_order)->value('id_branch');

        return (int) Branch::queryAllBranches()->whereKey($branchId)->value('id_company');
    }
}
