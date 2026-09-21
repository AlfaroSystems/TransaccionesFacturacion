<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\Retaceo;
use App\Models\RetaceoDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RetaceoService
{
    /**
     * Calcula el prorrateo de flete, gastos y aranceles (DAI) por línea de producto.
     *
     * @param  array $items
     * @param  float $totalFreight
     * @param  float $totalExpenses
     * @return array{items: array, totals: array{total_fob: float, total_freight: float, total_expenses: float, total_dai: float, total_cost: float}}
     */
    public function calcularProrrateo(array $items, float $totalFreight, float $totalExpenses): array
    {
        $totalFob = 0.0;
        foreach ($items as $item) {
            $totalFob += (float) ($item['cost_fob'] ?? 0);
        }

        $totalDai = 0.0;
        $processedItems = [];
        $count = count($items);

        foreach ($items as $item) {
            $qty     = max(0.0001, (float) ($item['quantity'] ?? 1));
            $costFob = (float) ($item['cost_fob'] ?? 0);

            // Factor de distribución según valor FOB
            $ratio = ($totalFob > 0) ? ($costFob / $totalFob) : ($count > 0 ? (1 / $count) : 0);

            $freightAmount = round($totalFreight * $ratio, 4);
            $expenseAmount = round($totalExpenses * $ratio, 4);
            $daiAmount     = round((float) ($item['dai_amount'] ?? 0), 4);

            $totalLineCost = round($costFob + $freightAmount + $expenseAmount + $daiAmount, 4);
            $unitCost      = round($totalLineCost / $qty, 4);

            $totalDai += $daiAmount;

            $processedItems[] = array_merge($item, [
                'quantity'       => $qty,
                'cost_fob'       => round($costFob, 4),
                'freight_amount' => $freightAmount,
                'expense_amount' => $expenseAmount,
                'dai_amount'     => $daiAmount,
                'unit_cost'      => $unitCost,
                'total_cost'     => $totalLineCost,
            ]);
        }

        $totalCost = round($totalFob + $totalFreight + $totalExpenses + $totalDai, 4);

        return [
            'items'  => $processedItems,
            'totals' => [
                'total_fob'      => round($totalFob, 4),
                'total_freight'  => round($totalFreight, 4),
                'total_expenses' => round($totalExpenses, 4),
                'total_dai'      => round($totalDai, 4),
                'total_cost'     => $totalCost,
            ],
        ];
    }

    /**
     * Registra un nuevo cálculo de retaceo y sus líneas liquidadas.
     *
     * @param  array $validated
     * @return Retaceo
     */
    public function crear(array $validated): Retaceo
    {
        return DB::transaction(function () use ($validated) {
            $purchase = Purchase::findOrFail($validated['id_purchase']);

            $totalFreight  = (float) ($validated['total_freight'] ?? 0);
            $totalExpenses = (float) ($validated['total_expenses'] ?? 0);

            $prorrateo = $this->calcularProrrateo($validated['details'], $totalFreight, $totalExpenses);

            $retaceo = Retaceo::create([
                'id_supplier'           => $validated['id_supplier'] ?? $purchase->id_supplier,
                'id_purchase'           => $purchase->id_purchase,
                'retaceo_date'          => $validated['retaceo_date'] ?? now(),
                'origin_country'        => $validated['origin_country'] ?? null,
                'import_invoice_number' => $validated['import_invoice_number'] ?? $purchase->supplier_invoice_number,
                'import_invoice_date'   => $validated['import_invoice_date'] ?? $purchase->supplier_invoice_date,
                'import_policy_number'  => $validated['import_policy_number'] ?? null,
                'import_policy_date'    => $validated['import_policy_date'] ?? null,
                'total_fob'             => $prorrateo['totals']['total_fob'],
                'total_freight'         => $prorrateo['totals']['total_freight'],
                'total_expenses'        => $prorrateo['totals']['total_expenses'],
                'total_dai'             => $prorrateo['totals']['total_dai'],
                'total_cost'            => $prorrateo['totals']['total_cost'],
                'status'                => $validated['status'] ?? 'calculated',
                'notes'                 => $validated['notes'] ?? null,
                'id_user'               => Auth::id(),
            ]);

            foreach ($prorrateo['items'] as $line) {
                RetaceoDetail::create([
                    'id_retaceo'         => $retaceo->id_retaceo,
                    'id_purchase_detail' => $line['id_purchase_detail'] ?? null,
                    'id_product'         => $line['id_product'],
                    'quantity'           => $line['quantity'],
                    'cost_fob'           => $line['cost_fob'],
                    'freight_amount'     => $line['freight_amount'],
                    'expense_amount'     => $line['expense_amount'],
                    'dai_amount'         => $line['dai_amount'],
                    'unit_cost'          => $line['unit_cost'],
                    'total_cost'         => $line['total_cost'],
                ]);
            }

            return $retaceo;
        });
    }

    /**
     * Actualiza un retaceo existente en borrador.
     */
    public function actualizar(Retaceo $retaceo, array $validated): Retaceo
    {
        if ($retaceo->status !== 'draft') {
            throw new InvalidArgumentException('Solo se pueden editar retaceos en estado borrador.');
        }

        return DB::transaction(function () use ($retaceo, $validated) {
            $totalFreight  = (float) ($validated['total_freight'] ?? 0);
            $totalExpenses = (float) ($validated['total_expenses'] ?? 0);

            $prorrateo = $this->calcularProrrateo($validated['details'], $totalFreight, $totalExpenses);

            $retaceo->update([
                'id_supplier'           => $validated['id_supplier'] ?? $retaceo->id_supplier,
                'retaceo_date'          => $validated['retaceo_date'] ?? $retaceo->retaceo_date,
                'origin_country'        => $validated['origin_country'] ?? $retaceo->origin_country,
                'import_invoice_number' => $validated['import_invoice_number'] ?? $retaceo->import_invoice_number,
                'import_invoice_date'   => $validated['import_invoice_date'] ?? $retaceo->import_invoice_date,
                'import_policy_number'  => $validated['import_policy_number'] ?? $retaceo->import_policy_number,
                'import_policy_date'    => $validated['import_policy_date'] ?? $retaceo->import_policy_date,
                'total_fob'             => $prorrateo['totals']['total_fob'],
                'total_freight'         => $prorrateo['totals']['total_freight'],
                'total_expenses'        => $prorrateo['totals']['total_expenses'],
                'total_dai'             => $prorrateo['totals']['total_dai'],
                'total_cost'            => $prorrateo['totals']['total_cost'],
                'notes'                 => $validated['notes'] ?? $retaceo->notes,
            ]);

            $retaceo->details()->delete();
            foreach ($prorrateo['items'] as $line) {
                RetaceoDetail::create([
                    'id_retaceo'         => $retaceo->id_retaceo,
                    'id_purchase_detail' => $line['id_purchase_detail'] ?? null,
                    'id_product'         => $line['id_product'],
                    'quantity'           => $line['quantity'],
                    'cost_fob'           => $line['cost_fob'],
                    'freight_amount'     => $line['freight_amount'],
                    'expense_amount'     => $line['expense_amount'],
                    'dai_amount'         => $line['dai_amount'],
                    'unit_cost'          => $line['unit_cost'],
                    'total_cost'         => $line['total_cost'],
                ]);
            }

            return $retaceo;
        });
    }

    /**
     * Cambia el estado del retaceo.
     */
    public function cambiarEstado(Retaceo $retaceo, string $nuevoEstado): void
    {
        $validos = ['draft', 'calculated', 'applied', 'cancelled'];
        if (!in_array($nuevoEstado, $validos, true)) {
            throw new InvalidArgumentException("Estado '{$nuevoEstado}' no reconocido.");
        }

        if ($retaceo->status === 'cancelled') {
            throw new InvalidArgumentException('Un retaceo cancelado no puede modificarse.');
        }

        $retaceo->update(['status' => $nuevoEstado]);
    }
}
