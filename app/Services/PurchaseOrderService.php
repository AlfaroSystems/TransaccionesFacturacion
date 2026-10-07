<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\PurchaseOrderExpense;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    /**
     * Cambios de estado que se hacen a mano. Recibida parcial y Completada no están: las
     * calcula PurchaseService según lo recibido. Completada, Cerrada y Cancelada son finales.
     */
    private const TRANSITIONS = [
        'draft'            => ['issued', 'cancelled'],
        'issued'           => ['cancelled'],
        'partial_received' => ['closed'],
    ];

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

            // id_purchase_quotation no se toca: la orden conserva la cotización de la que salió
            $order->update([
                'id_supplier'           => $validated['id_supplier'],
                'id_branch'             => $validated['id_branch'],
                'id_warehouse'          => $validated['id_warehouse'],
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

            // Reemplazar detalles y gastos (uno por uno, para que cada borrado quede en la bitácora)
            $order->details()->get()->each->delete();
            $order->expenses()->get()->each->delete();

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
        if (! in_array($nuevoEstado, self::TRANSITIONS[$order->status] ?? [], true)) {
            throw new \InvalidArgumentException(sprintf(
                'Una orden %s no puede pasar a %s.',
                mb_strtolower(PurchaseOrder::STATUS_LABELS[$order->status] ?? $order->status),
                mb_strtolower(PurchaseOrder::STATUS_LABELS[$nuevoEstado] ?? $nuevoEstado)
            ));
        }

        // Con compras registradas (aunque sean borradores) no se cancela: si ya llegó algo
        // se cierra; los borradores se eliminan o anulan antes
        if ($nuevoEstado === 'cancelled' && Purchase::queryAllBranches()
            ->where('id_purchase_order', $order->id_purchase_order)
            ->where('status', '!=', 'cancelled')
            ->exists()) {
            throw new \InvalidArgumentException('No se puede cancelar una orden con compras registradas: elimine o anule sus borradores.');
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
                // Si se reparte en el costo de los productos (ver ProductCostService)
                'is_costable'       => (bool) ($expense['is_costable'] ?? true),
            ]);
        }
    }
}
