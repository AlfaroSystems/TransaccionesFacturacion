<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\Retaceo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Compras (recepción de mercadería) contra órdenes de compra.
 *
 * - Solo cuentan como recibidas las compras recibidas o completadas; el borrador no.
 * - Ninguna línea puede recibir más de lo que falta de su línea de orden, ni productos
 *   que no estén en la orden.
 * - El estado de la orden (emitida, recibida parcial, completada) se recalcula con cada
 *   cambio: crear, editar, cambiar de estado o eliminar una compra.
 */
class PurchaseService
{
    /**
     * Cambios de estado permitidos. Completada y Anulada son finales; anular una recibida
     * exige que no tenga un retaceo activo.
     */
    private const TRANSITIONS = [
        'draft'    => ['received', 'completed', 'cancelled'],
        'received' => ['completed', 'cancelled'],
    ];

    /** Estados de orden en los que se puede registrar mercadería */
    public const RECEIVABLE_ORDER_STATUSES = ['issued', 'partial_received'];

    public function __construct(private readonly ProductCostService $costos = new ProductCostService()) {}

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
            // Se bloquea la orden: dos recepciones a la vez no deben superar lo ordenado
            $order = $this->ordenBloqueada($validated['id_purchase_order']);

            if (! in_array($order->status, self::RECEIVABLE_ORDER_STATUSES, true)) {
                throw ValidationException::withMessages([
                    'id_purchase_order' => 'Solo se puede registrar mercadería de órdenes emitidas o recibidas parcialmente.',
                ]);
            }

            $this->validarExcedentes($order, $validated['details']);

            $totales = $this->calcularTotales($validated['details']);

            $purchase = Purchase::create([
                'id_purchase_order'       => $order->id_purchase_order,
                'id_supplier'             => $validated['id_supplier'] ?? $order->id_supplier,
                'id_branch'               => $order->id_branch,
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

            // Una compra que nace completada fija el último costo de sus productos
            $this->costos->actualizarDesdeCompra($purchase);

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
            $order = $purchase->id_purchase_order ? $this->ordenBloqueada($purchase->id_purchase_order) : null;

            if ($order) {
                $this->validarExcedentes($order, $validated['details'], $purchase->id_purchase);
            }

            $totales = $this->calcularTotales($validated['details']);

            $purchase->update([
                'id_supplier'             => $validated['id_supplier'] ?? $purchase->id_supplier,
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

            // Reemplazar detalles (uno por uno, para que cada borrado quede en la bitácora)
            $purchase->details()->get()->each->delete();
            $this->guardarDetalles($purchase, $validated['details']);

            if ($order) {
                $this->actualizarEstadoOrden($order);
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
        if (! in_array($nuevoEstado, self::TRANSITIONS[$purchase->status] ?? [], true)) {
            throw new InvalidArgumentException(sprintf(
                'Una compra %s no puede pasar a %s.',
                mb_strtolower(Purchase::STATUS_LABELS[$purchase->status] ?? $purchase->status),
                mb_strtolower(Purchase::STATUS_LABELS[$nuevoEstado] ?? $nuevoEstado)
            ));
        }

        if ($nuevoEstado === 'cancelled' && Retaceo::queryAllBranches()
            ->where('id_purchase', $purchase->id_purchase)
            ->where('status', '!=', 'cancelled')
            ->exists()) {
            throw new InvalidArgumentException('No se puede anular una compra con un retaceo activo: cancele primero el retaceo.');
        }

        DB::transaction(function () use ($purchase, $nuevoEstado) {
            $order = $purchase->id_purchase_order ? $this->ordenBloqueada($purchase->id_purchase_order) : null;

            // Al confirmar un borrador, otras compras pudieron recibirse después de crearlo
            $confirma = in_array($nuevoEstado, Purchase::RECEIVED_STATUSES, true)
                && ! in_array($purchase->status, Purchase::RECEIVED_STATUSES, true);

            if ($order && $confirma) {
                if (! in_array($order->status, self::RECEIVABLE_ORDER_STATUSES, true)) {
                    throw new InvalidArgumentException('No se puede confirmar la recepción: la orden de compra ya no admite recepciones ('
                        . mb_strtolower(PurchaseOrder::STATUS_LABELS[$order->status] ?? $order->status) . ').');
                }

                $lineas = $purchase->details()->get()->map(fn ($d) => [
                    'id_purchase_order_detail' => $d->id_purchase_order_detail,
                    'id_product'               => $d->id_product,
                    'quantity_received'        => $d->quantity_received,
                ])->all();

                $excedentes = $this->excedentes($order, $lineas, $purchase->id_purchase);
                if ($excedentes) {
                    throw new InvalidArgumentException('No se puede confirmar la recepción. ' . reset($excedentes));
                }
            }

            $purchase->update(['status' => $nuevoEstado]);

            if ($order) {
                $this->actualizarEstadoOrden($order);
            }

            if ($nuevoEstado === 'completed') {
                $this->costos->actualizarDesdeCompra($purchase);
            }
        });
    }

    /**
     * Elimina una compra (solo borradores, ver el controlador) y recalcula su orden.
     */
    public function eliminar(Purchase $purchase): void
    {
        DB::transaction(function () use ($purchase) {
            $order = $purchase->id_purchase_order ? $this->ordenBloqueada($purchase->id_purchase_order) : null;

            $purchase->delete();

            if ($order) {
                $this->actualizarEstadoOrden($order);
            }
        });
    }

    /**
     * Guarda las líneas de detalle de la compra.
     */
    private function guardarDetalles(Purchase $purchase, array $details): void
    {
        // La unidad es la de la línea de la orden: lo recibido y el costo se cuentan en ella
        $unidades = PurchaseOrderDetail::whereIn('id_purchase_order_detail', array_column($details, 'id_purchase_order_detail'))
            ->pluck('id_unit', 'id_purchase_order_detail');

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
                'id_unit'                  => $unidades->get($item['id_purchase_order_detail'] ?? 0, $item['id_unit'] ?? null),
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
     * Recalcula el estado de la orden según lo recibido en compras confirmadas: emitida
     * (nada), recibida parcial o completada. Las órdenes en borrador o canceladas no cambian.
     */
    public function actualizarEstadoOrden(PurchaseOrder $order): void
    {
        if (! in_array($order->status, ['issued', 'partial_received', 'completed'], true)) {
            return;
        }

        $orderDetails = $order->details()->get();
        if ($orderDetails->isEmpty()) {
            return;
        }

        $received = $this->recibidoPorLinea($orderDetails->pluck('id_purchase_order_detail'));

        $allComplete = true;
        $anyReceived = false;

        foreach ($orderDetails as $detail) {
            $receivedQty = (float) ($received[$detail->id_purchase_order_detail] ?? 0);

            if ($receivedQty > 0) {
                $anyReceived = true;
            }
            if ($receivedQty < (float) $detail->quantity) {
                $allComplete = false;
            }
        }

        $status = $allComplete ? 'completed' : ($anyReceived ? 'partial_received' : 'issued');

        if ($order->status !== $status) {
            $order->update(['status' => $status]);
        }
    }

    /**
     * Cantidad recibida de cada línea de orden en compras confirmadas (recibidas o
     * completadas), de cualquier sucursal; opcionalmente sin contar una compra.
     *
     * @return Collection<int, float> id_purchase_order_detail => cantidad
     */
    public function recibidoPorLinea(Collection $orderDetailIds, ?int $exceptPurchaseId = null): Collection
    {
        $confirmed = Purchase::queryAllBranches()
            ->whereIn('status', Purchase::RECEIVED_STATUSES)
            ->when($exceptPurchaseId, fn ($q) => $q->whereKeyNot($exceptPurchaseId))
            ->select('id_purchase');

        return PurchaseDetail::whereIn('id_purchase', $confirmed)
            ->whereIn('id_purchase_order_detail', $orderDetailIds)
            ->groupBy('id_purchase_order_detail')
            ->selectRaw('id_purchase_order_detail, SUM(quantity_received) as total_received')
            ->pluck('total_received', 'id_purchase_order_detail')
            ->map(fn ($qty) => (float) $qty);
    }

    /**
     * Líneas que reciben más de lo que falta de su línea de orden. Lo pendiente descuenta
     * lo recibido en otras compras confirmadas; los borradores no reservan cantidad.
     *
     * @return array<string, string> mensaje por campo (details.N.quantity_received)
     */
    private function excedentes(PurchaseOrder $order, array $details, ?int $exceptPurchaseId = null): array
    {
        $orderLines = $order->details()->with('product')->get()->keyBy('id_purchase_order_detail');
        $received = $this->recibidoPorLinea($orderLines->keys(), $exceptPurchaseId);
        $errors = [];

        foreach ($details as $key => $item) {
            $line = $orderLines->get((int) ($item['id_purchase_order_detail'] ?? 0));

            if (! $line) {
                $errors["details.{$key}.id_purchase_order_detail"] = 'Solo se pueden recibir productos de la orden de compra.';
                continue;
            }

            $pending = max(0, (float) $line->quantity - (float) ($received[$line->id_purchase_order_detail] ?? 0));

            if ((float) $item['quantity_received'] > $pending + 0.00001) {
                $name = $line->product?->name ?? 'el producto';
                $errors["details.{$key}.quantity_received"] = "De {$name} solo faltan "
                    . rtrim(rtrim(number_format($pending, 4, '.', ''), '0'), '.') . ' por recibir.';
            }
        }

        return $errors;
    }

    private function validarExcedentes(PurchaseOrder $order, array $details, ?int $exceptPurchaseId = null): void
    {
        $errors = $this->excedentes($order, $details, $exceptPurchaseId);

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Orden de compra bloqueada hasta el fin de la transacción. El acceso ya lo validó el
     * controlador (la orden de la compra o la elegida en el formulario).
     */
    private function ordenBloqueada(int|string $orderId): PurchaseOrder
    {
        return PurchaseOrder::queryAllBranches()->whereKey($orderId)->lockForUpdate()->firstOrFail();
    }
}
