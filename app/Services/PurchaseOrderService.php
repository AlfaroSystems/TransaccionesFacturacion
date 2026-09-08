<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\PurchaseOrderExpense;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    /**
     * Calcula los totales consolidados (subtotal, descuento, impuestos, gastos) de una OC.
     *
     * @param  array $products  Lista de líneas de producto validadas.
     * @param  array $expenses  Lista de gastos adicionales validados.
     * @return array{subtotal: float, discount: float, tax: float, additional_expenses: float, total: float}
     */
    public function calcularTotales(array $products, array $expenses = []): array
    {
        $subtotal = 0.0;
        $discount = 0.0;
        $tax      = 0.0;

        foreach ($products as $product) {
            $lineSubtotal = (float) $product['quantity'] * (float) $product['unit_price'];
            $lineDiscount = (float) ($product['discount'] ?? 0);
            $base         = max(0, $lineSubtotal - $lineDiscount);
            $taxRate      = (float) ($product['tax_rate'] ?? 0);
            $taxAmount    = $base * ($taxRate / 100);

            $subtotal += $lineSubtotal;
            $discount += $lineDiscount;
            $tax      += $taxAmount;
        }

        $additionalExpenses = 0.0;
        foreach ($expenses as $expense) {
            $additionalExpenses += (float) ($expense['amount'] ?? 0);
        }

        $total = max(0, $subtotal - $discount) + $tax + $additionalExpenses;

        return [
            'subtotal'             => $subtotal,
            'discount'             => $discount,
            'tax'                  => $tax,
            'additional_expenses'  => $additionalExpenses,
            'total'                => $total,
        ];
    }

    /**
     * Crea una nueva Orden de Compra con sus líneas y gastos en una sola transacción.
     *
     * @param  array $validated Datos validados del Request.
     * @return PurchaseOrder
     */
    public function crear(array $validated): PurchaseOrder
    {
        return DB::transaction(function () use ($validated) {
            $totales = $this->calcularTotales(
                $validated['products'],
                $validated['expenses'] ?? []
            );

            $order = PurchaseOrder::create([
                'id_supplier'           => $validated['id_supplier'],
                'id_branch'             => $validated['id_branch'],
                'id_warehouse'          => $validated['id_warehouse'],
                'id_purchase_quotation' => $validated['id_purchase_quotation'] ?? null,
                'id_user'               => Auth::id(),
                'order_date'            => $validated['order_date'],
                'expected_date'         => $validated['expected_date'],
                'currency'              => strtoupper($validated['currency']),
                'payment_terms'         => $validated['payment_terms'],
                'subtotal'              => $totales['subtotal'],
                'discount'              => $totales['discount'],
                'tax'                   => $totales['tax'],
                'additional_expenses'   => $totales['additional_expenses'],
                'total'                 => $totales['total'],
                'status'                => 'draft',
                'notes'                 => $validated['notes'] ?? null,
            ]);

            $this->guardarDetalles($order, $validated['products']);
            $this->guardarGastos($order, $validated['expenses'] ?? []);

            return $order;
        });
    }

    /**
     * Actualiza una Orden de Compra existente en borrador con sus líneas y gastos.
     *
     * @param  PurchaseOrder $order
     * @param  array         $validated
     * @return PurchaseOrder
     */
    public function actualizar(PurchaseOrder $order, array $validated): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $validated) {
            $totales = $this->calcularTotales(
                $validated['products'],
                $validated['expenses'] ?? []
            );

            $order->update([
                'id_supplier'           => $validated['id_supplier'],
                'id_branch'             => $validated['id_branch'],
                'id_warehouse'          => $validated['id_warehouse'],
                'id_purchase_quotation' => $validated['id_purchase_quotation'] ?? null,
                'order_date'            => $validated['order_date'],
                'expected_date'         => $validated['expected_date'],
                'currency'              => strtoupper($validated['currency']),
                'payment_terms'         => $validated['payment_terms'],
                'subtotal'              => $totales['subtotal'],
                'discount'              => $totales['discount'],
                'tax'                   => $totales['tax'],
                'additional_expenses'   => $totales['additional_expenses'],
                'total'                 => $totales['total'],
                'notes'                 => $validated['notes'] ?? null,
            ]);

            // Reemplazar detalles y gastos
            $order->details()->delete();
            $order->expenses()->delete();

            $this->guardarDetalles($order, $validated['products']);
            $this->guardarGastos($order, $validated['expenses'] ?? []);

            return $order;
        });
    }

    /**
     * Cambia el estado de una orden aplicando las reglas de negocio.
     * Lanza una excepción de dominio si la transición no está permitida.
     *
     * @param  PurchaseOrder $order
     * @param  string        $nuevoEstado
     * @return void
     * @throws \InvalidArgumentException
     */
    public function cambiarEstado(PurchaseOrder $order, string $nuevoEstado): void
    {
        if ($order->status === 'cancelled') {
            throw new \InvalidArgumentException('Una orden cancelada no puede cambiar de estado.');
        }

        if ($order->status === 'issued' && $nuevoEstado === 'draft') {
            throw new \InvalidArgumentException('Una orden emitida no puede regresar a borrador.');
        }

        $order->update(['status' => $nuevoEstado]);
    }

    // -------------------------------------------------------------------------
    // Métodos privados de soporte
    // -------------------------------------------------------------------------

    /**
     * Persiste las líneas de detalle (productos) de la orden.
     */
    private function guardarDetalles(PurchaseOrder $order, array $products): void
    {
        foreach ($products as $product) {
            $lineSubtotal = (float) $product['quantity'] * (float) $product['unit_price'];
            $lineDiscount = (float) ($product['discount'] ?? 0);
            $base         = max(0, $lineSubtotal - $lineDiscount);
            $taxRate      = (float) ($product['tax_rate'] ?? 0);
            $taxAmount    = $base * ($taxRate / 100);
            $lineTotal    = $base + $taxAmount;

            PurchaseOrderDetail::create([
                'id_purchase_order' => $order->id_purchase_order,
                'id_product'        => $product['id_product'],
                'quantity'          => $product['quantity'],
                'id_unit'           => $product['id_unit'],
                'unit_price'        => $product['unit_price'],
                'discount'          => $lineDiscount,
                'subtotal'          => $lineSubtotal,
                'tax_rate'          => $taxRate,
                'tax_amount'        => $taxAmount,
                'total'             => $lineTotal,
                'notes'             => $product['notes'] ?? null,
            ]);
        }
    }

    /**
     * Persiste los gastos adicionales de la orden.
     */
    private function guardarGastos(PurchaseOrder $order, array $expenses): void
    {
        foreach ($expenses as $expense) {
            $amount = (float) ($expense['amount'] ?? 0);
            if ($amount <= 0) {
                continue;
            }

            PurchaseOrderExpense::create([
                'id_purchase_order' => $order->id_purchase_order,
                'id_expense_type'   => $expense['id_expense_type'],
                'description'       => $expense['description'] ?? null,
                'amount'            => $amount,
            ]);
        }
    }
}
