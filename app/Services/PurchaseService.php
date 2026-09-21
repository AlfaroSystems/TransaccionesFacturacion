<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PurchaseService
{
    /**
     * Calcula los totales consolidados (subtotal, descuento, impuestos, total) a partir de los detalles.
     *
     * @param  array $details
     * @return array{subtotal: float, discount: float, tax: float, total: float}
     */
    public function calcularTotales(array $details): array
    {
        $subtotal = 0.0;
        $discount = 0.0;
        $tax      = 0.0;

        foreach ($details as $item) {
            $qty          = (float) ($item['quantity_received'] ?? 0);
            $price        = (float) ($item['unit_price'] ?? 0);
            $lineSubtotal = $qty * $price;
            $lineDiscount = (float) ($item['discount'] ?? 0);
            $base         = max(0, $lineSubtotal - $lineDiscount);
            $taxRate      = (float) ($item['tax_rate'] ?? 0);
            $taxAmount    = $base * ($taxRate / 100);

            $subtotal += $lineSubtotal;
            $discount += $lineDiscount;
            $tax      += $taxAmount;
        }

        $total = max(0, $subtotal - $discount) + $tax;

        return [
            'subtotal' => round($subtotal, 4),
            'discount' => round($discount, 4),
            'tax'      => round($tax, 4),
            'total'    => round($total, 4),
        ];
    }

    /**
     * Registra una nueva compra/recepción vinculada a una Orden de Compra.
     *
     * @param  array $validated
     * @return Purchase
     */
    public function crear(array $validated): Purchase
    {
        return DB::transaction(function () use ($validated) {
            $totales = $this->calcularTotales($validated['details']);

            $order = PurchaseOrder::findOrFail($validated['id_purchase_order']);

            $purchase = Purchase::create([
                'id_purchase_order'       => $order->id_purchase_order,
                'id_supplier'             => $validated['id_supplier'] ?? $order->id_supplier,
                'id_branch'               => $validated['id_branch'] ?? $order->id_branch,
                'id_warehouse'            => $validated['id_warehouse'] ?? $order->id_warehouse,
                'purchase_date'           => $validated['purchase_date'] ?? now(),
                'supplier_invoice_number' => $validated['supplier_invoice_number'] ?? null,
                'supplier_invoice_date'   => $validated['supplier_invoice_date'] ?? null,
                'currency'                => strtoupper($validated['currency'] ?? $order->currency ?? 'USD'),
                'subtotal'                => $totales['subtotal'],
                'discount'                => $totales['discount'],
                'tax'                     => $totales['tax'],
                'total'                   => $totales['total'],
                'status'                  => $validated['status'] ?? 'completed',
                'notes'                   => $validated['notes'] ?? null,
                'id_user'                 => Auth::id(),
            ]);

            $this->guardarDetalles($purchase, $validated['details']);

            // Actualizar estado de la Orden de Compra de acuerdo a las cantidades recibidas
            $this->actualizarEstadoOrden($order);

            return $purchase;
        });
    }

    /**
     * Actualiza una compra existente si se encuentra en estado borrador.
     *
     * @param  Purchase $purchase
     * @param  array    $validated
     * @return Purchase
     */
    public function actualizar(Purchase $purchase, array $validated): Purchase
    {
        if ($purchase->status !== 'draft') {
            throw new InvalidArgumentException('Solo se pueden editar compras en estado borrador.');
        }

        return DB::transaction(function () use ($purchase, $validated) {
            $totales = $this->calcularTotales($validated['details']);

            $purchase->update([
                'id_supplier'             => $validated['id_supplier'] ?? $purchase->id_supplier,
                'id_branch'               => $validated['id_branch'] ?? $purchase->id_branch,
                'id_warehouse'            => $validated['id_warehouse'] ?? $purchase->id_warehouse,
                'purchase_date'           => $validated['purchase_date'] ?? $purchase->purchase_date,
                'supplier_invoice_number' => $validated['supplier_invoice_number'] ?? $purchase->supplier_invoice_number,
                'supplier_invoice_date'   => $validated['supplier_invoice_date'] ?? $purchase->supplier_invoice_date,
                'currency'                => strtoupper($validated['currency'] ?? $purchase->currency),
                'subtotal'                => $totales['subtotal'],
                'discount'                => $totales['discount'],
                'tax'                     => $totales['tax'],
                'total'                   => $totales['total'],
                'notes'                   => $validated['notes'] ?? $purchase->notes,
            ]);

            // Reemplazar detalles
            $purchase->details()->delete();
            $this->guardarDetalles($purchase, $validated['details']);

            if ($purchase->purchaseOrder) {
                $this->actualizarEstadoOrden($purchase->purchaseOrder);
            }

            return $purchase;
        });
    }

    /**
     * Cambia el estado de una compra.
     *
     * @param  Purchase $purchase
     * @param  string   $nuevoEstado
     * @return void
     */
    public function cambiarEstado(Purchase $purchase, string $nuevoEstado): void
    {
        $validos = ['draft', 'received', 'completed', 'cancelled'];
        if (!in_array($nuevoEstado, $validos, true)) {
            throw new InvalidArgumentException("Estado '{$nuevoEstado}' no reconocido.");
        }

        if ($purchase->status === 'cancelled') {
            throw new InvalidArgumentException('Una compra cancelada no puede ser modificada.');
        }

        $purchase->update(['status' => $nuevoEstado]);

        if ($purchase->purchaseOrder) {
            $this->actualizarEstadoOrden($purchase->purchaseOrder);
        }
    }

    /**
     * Guarda las líneas de detalle de la compra.
     */
    private function guardarDetalles(Purchase $purchase, array $details): void
    {
        foreach ($details as $item) {
            $qtyReceived  = (float) ($item['quantity_received'] ?? 0);
            $qtyOrdered   = (float) ($item['quantity_ordered'] ?? $qtyReceived);
            $unitPrice    = (float) ($item['unit_price'] ?? 0);
            $lineDiscount = (float) ($item['discount'] ?? 0);
            $subtotal     = $qtyReceived * $unitPrice;
            $base         = max(0, $subtotal - $lineDiscount);
            $taxRate      = (float) ($item['tax_rate'] ?? 0);
            $taxAmount    = $base * ($taxRate / 100);
            $total        = $base + $taxAmount;

            PurchaseDetail::create([
                'id_purchase'              => $purchase->id_purchase,
                'id_purchase_order_detail' => $item['id_purchase_order_detail'] ?? null,
                'id_product'               => $item['id_product'],
                'quantity_ordered'         => $qtyOrdered,
                'quantity_received'        => $qtyReceived,
                'id_unit'                  => $item['id_unit'] ?? null,
                'unit_price'               => $unitPrice,
                'discount'                 => $lineDiscount,
                'subtotal'                 => $subtotal,
                'tax_rate'                 => $taxRate,
                'tax_amount'               => $taxAmount,
                'total'                    => $total,
                'notes'                    => $item['notes'] ?? null,
            ]);
        }
    }

    /**
     * Actualiza el estado de la orden de compra en función del total recibido.
     */
    private function actualizarEstadoOrden(PurchaseOrder $order): void
    {
        $orderDetails = $order->details()->get();
        if ($orderDetails->isEmpty()) {
            return;
        }

        // Obtener la suma total recibida por cada línea de orden en compras no canceladas
        $receivedSums = PurchaseDetail::whereHas('purchase', function ($q) {
            $q->where('status', '!=', 'cancelled');
        })
        ->whereIn('id_purchase_order_detail', $orderDetails->pluck('id_purchase_order_detail'))
        ->groupBy('id_purchase_order_detail')
        ->selectRaw('id_purchase_order_detail, SUM(quantity_received) as total_received')
        ->pluck('total_received', 'id_purchase_order_detail');

        $allComplete = true;
        $anyReceived = false;

        foreach ($orderDetails as $detail) {
            $ordered = (float) $detail->quantity;
            $received = (float) ($receivedSums[$detail->id_purchase_order_detail] ?? 0);

            if ($received > 0) {
                $anyReceived = true;
            }
            if ($received < $ordered) {
                $allComplete = false;
            }
        }

        if ($allComplete) {
            $order->update(['status' => 'completed']);
        } elseif ($anyReceived) {
            $order->update(['status' => 'partial_received']);
        }
    }
}
