<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\Retaceo;
use App\Models\RetaceoDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class RetaceoService
{
    public function __construct(
        private readonly ProductCostService $costos = new ProductCostService,
        private readonly InventoryService $inventario = new InventoryService,
    ) {}

    /**
     * Calcula el prorrateo de flete, gastos y aranceles (DAI) por línea de producto.
     *
     * Como en la hoja de retaceo, cada total de la póliza se reparte según el valor FOB de la
     * línea: FOB de la línea × (total / FOB total).
     *
     * @return array{items: array, totals: array{total_fob: float, total_freight: float, total_expenses: float, total_dai: float, total_cost: float}}
     */
    public function calcularProrrateo(array $items, float $totalFreight, float $totalExpenses, float $totalDai = 0): array
    {
        $totalFob = 0.0;
        foreach ($items as $item) {
            $totalFob += (float) ($item['cost_fob'] ?? 0);
        }

        // Columna de la línea que recibe cada total
        $totales = [
            'freight_amount' => $totalFreight,
            'expense_amount' => $totalExpenses,
            'dai_amount' => $totalDai,
        ];
        $count = count($items);
        $lines = [];

        // 1. Reparto de flete, gastos y DAI según el valor FOB de cada línea
        foreach (array_values($items) as $item) {
            $costFob = (float) ($item['cost_fob'] ?? 0);

            // Factor de distribución según valor FOB
            $ratio = ($totalFob > 0) ? ($costFob / $totalFob) : ($count > 0 ? (1 / $count) : 0);

            $line = ['item' => $item, 'cost_fob' => $costFob];
            foreach ($totales as $campo => $total) {
                $line[$campo] = round($total * $ratio, 4);
            }
            $lines[] = $line;
        }

        // 2. El redondeo de cada línea puede dejar una diferencia (100 entre 3 = 99.9999);
        //    se asigna a la última línea para que el reparto sume exactamente el total
        if ($count > 0) {
            $last = $count - 1;
            foreach ($totales as $campo => $total) {
                $lines[$last][$campo] = round(
                    $lines[$last][$campo] + $total - array_sum(array_column($lines, $campo)),
                    4
                );
            }
        }

        // 3. Costo total y unitario de cada línea
        $processedItems = [];
        foreach ($lines as $line) {
            $item = $line['item'];
            $qty = max(0.0001, (float) ($item['quantity'] ?? 1));
            $totalLineCost = round($line['cost_fob'] + $line['freight_amount'] + $line['expense_amount'] + $line['dai_amount'], 4);

            $processedItems[] = array_merge($item, [
                'quantity' => $qty,
                'cost_fob' => round($line['cost_fob'], 4),
                'freight_amount' => $line['freight_amount'],
                'expense_amount' => $line['expense_amount'],
                'dai_amount' => $line['dai_amount'],
                'unit_cost' => round($totalLineCost / $qty, 4),
                'total_cost' => $totalLineCost,
            ]);
        }

        $totalCost = round($totalFob + $totalFreight + $totalExpenses + $totalDai, 4);

        return [
            'items' => $processedItems,
            'totals' => [
                'total_fob' => round($totalFob, 4),
                'total_freight' => round($totalFreight, 4),
                'total_expenses' => round($totalExpenses, 4),
                'total_dai' => round($totalDai, 4),
                'total_cost' => $totalCost,
            ],
        ];
    }

    /**
     * Registra un nuevo cálculo de retaceo y sus líneas liquidadas.
     */
    public function crear(array $validated): Retaceo
    {
        return DB::transaction(function () use ($validated) {
            // Se bloquea la compra: dos retaceos creados a la vez no pasan ambos la revisión, y
            // la compra no se anula mientras tanto (anularla también la bloquea)
            $purchase = Purchase::whereKey($validated['id_purchase'])->lockForUpdate()->firstOrFail();

            if (! in_array($purchase->status, Purchase::RECEIVED_STATUSES, true)) {
                throw ValidationException::withMessages([
                    'id_purchase' => 'La compra debe estar recibida o completada para calcular su retaceo.',
                ]);
            }

            if (Retaceo::queryAllBranches()->where('id_purchase', $purchase->id_purchase)->where('status', '!=', 'cancelled')->exists()) {
                throw ValidationException::withMessages([
                    'id_purchase' => 'Esta compra ya tiene un retaceo activo. Edítalo o cancélalo antes de crear otro.',
                ]);
            }

            $totalFreight = (float) ($validated['total_freight'] ?? 0);
            $totalExpenses = $this->totalDeGastos($validated['expenses'] ?? []);
            $totalDai = (float) ($validated['total_dai'] ?? 0);

            $prorrateo = $this->calcularProrrateo($validated['details'], $totalFreight, $totalExpenses, $totalDai);

            $retaceo = Retaceo::create([
                'id_supplier' => $validated['id_supplier'] ?? $purchase->id_supplier,
                'id_purchase' => $purchase->id_purchase,
                'retaceo_date' => $validated['retaceo_date'] ?? now(),
                'origin_country' => $validated['origin_country'] ?? null,
                'import_invoice_number' => $validated['import_invoice_number'] ?? $purchase->supplier_invoice_number,
                'import_invoice_date' => $validated['import_invoice_date'] ?? $purchase->supplier_invoice_date,
                'import_policy_number' => $validated['import_policy_number'] ?? null,
                'import_policy_date' => $validated['import_policy_date'] ?? null,
                'total_fob' => $prorrateo['totals']['total_fob'],
                'total_freight' => $prorrateo['totals']['total_freight'],
                'total_expenses' => $prorrateo['totals']['total_expenses'],
                'total_dai' => $prorrateo['totals']['total_dai'],
                'total_cost' => $prorrateo['totals']['total_cost'],
                'status' => $validated['status'] ?? 'calculated',
                'notes' => $validated['notes'] ?? null,
                'id_user' => Auth::id(),
            ]);

            foreach ($prorrateo['items'] as $line) {
                RetaceoDetail::create([
                    'id_retaceo' => $retaceo->id_retaceo,
                    'id_purchase_detail' => $line['id_purchase_detail'] ?? null,
                    'id_product' => $line['id_product'],
                    'quantity' => $line['quantity'],
                    'cost_fob' => $line['cost_fob'],
                    'freight_amount' => $line['freight_amount'],
                    'expense_amount' => $line['expense_amount'],
                    'dai_amount' => $line['dai_amount'],
                    'unit_cost' => $line['unit_cost'],
                    'total_cost' => $line['total_cost'],
                ]);
            }

            $this->guardarGastos($retaceo, $validated['expenses'] ?? []);

            // Un retaceo que nace aplicado fija el costo de los productos de su compra y crea
            // sus lotes por ubicar
            if ($retaceo->status === 'applied') {
                $this->costos->actualizarDesdeCompra($purchase);
                $this->inventario->crearLotes($purchase);
            }

            return $retaceo;
        });
    }

    /**
     * Actualiza un retaceo existente en borrador.
     */
    public function actualizar(Retaceo $retaceo, array $validated): Retaceo
    {
        return DB::transaction(function () use ($retaceo, $validated) {
            // Otro usuario pudo liquidarlo después de que se abrió el formulario
            if ($retaceo->refreshWithLock()->status !== 'draft') {
                throw new InvalidArgumentException('Solo se pueden editar retaceos en estado borrador.');
            }

            $totalFreight = (float) ($validated['total_freight'] ?? 0);
            $totalExpenses = $this->totalDeGastos($validated['expenses'] ?? []);
            $totalDai = (float) ($validated['total_dai'] ?? 0);

            $prorrateo = $this->calcularProrrateo($validated['details'], $totalFreight, $totalExpenses, $totalDai);

            $retaceo->update([
                'id_supplier' => $validated['id_supplier'] ?? $retaceo->id_supplier,
                'retaceo_date' => $validated['retaceo_date'] ?? $retaceo->retaceo_date,
                'origin_country' => $validated['origin_country'] ?? $retaceo->origin_country,
                'import_invoice_number' => $validated['import_invoice_number'] ?? $retaceo->import_invoice_number,
                'import_invoice_date' => $validated['import_invoice_date'] ?? $retaceo->import_invoice_date,
                'import_policy_number' => $validated['import_policy_number'] ?? $retaceo->import_policy_number,
                'import_policy_date' => $validated['import_policy_date'] ?? $retaceo->import_policy_date,
                'total_fob' => $prorrateo['totals']['total_fob'],
                'total_freight' => $prorrateo['totals']['total_freight'],
                'total_expenses' => $prorrateo['totals']['total_expenses'],
                'total_dai' => $prorrateo['totals']['total_dai'],
                'total_cost' => $prorrateo['totals']['total_cost'],
                'notes' => $validated['notes'] ?? $retaceo->notes,
            ]);

            // Reemplazar detalles (uno por uno, para que cada borrado quede en la bitácora)
            $retaceo->details()->get()->each->delete();
            foreach ($prorrateo['items'] as $line) {
                RetaceoDetail::create([
                    'id_retaceo' => $retaceo->id_retaceo,
                    'id_purchase_detail' => $line['id_purchase_detail'] ?? null,
                    'id_product' => $line['id_product'],
                    'quantity' => $line['quantity'],
                    'cost_fob' => $line['cost_fob'],
                    'freight_amount' => $line['freight_amount'],
                    'expense_amount' => $line['expense_amount'],
                    'dai_amount' => $line['dai_amount'],
                    'unit_cost' => $line['unit_cost'],
                    'total_cost' => $line['total_cost'],
                ]);
            }

            $retaceo->expenses()->get()->each->delete();
            $this->guardarGastos($retaceo, $validated['expenses'] ?? []);

            return $retaceo;
        });
    }

    /**
     * Total de otros gastos aduanales / seguro: la suma de sus líneas.
     *
     * @param  array<int, array{amount: numeric}>  $gastos
     */
    private function totalDeGastos(array $gastos): float
    {
        return round(array_sum(array_map(fn ($g) => (float) $g['amount'], $gastos)), 4);
    }

    /**
     * @param  array<int, array{id_expense_type: int, description?: ?string, amount: numeric}>  $gastos
     */
    private function guardarGastos(Retaceo $retaceo, array $gastos): void
    {
        foreach ($gastos as $gasto) {
            $retaceo->expenses()->create([
                'id_expense_type' => $gasto['id_expense_type'],
                'description' => $gasto['description'] ?? null,
                'amount' => round((float) $gasto['amount'], 4),
            ]);
        }
    }

    /**
     * Cambia el estado del retaceo.
     */
    public function cambiarEstado(Retaceo $retaceo, string $nuevoEstado): void
    {
        // Borrador → Liquidado → Aplicado; se cancela antes de aplicarlo. Un aplicado vuelve a
        // borrador (para corregirlo) solo mientras sus lotes sigan por ubicar: después, sus
        // costos ya entraron al inventario y no deben cambiar
        $transitions = [
            'draft' => ['calculated', 'cancelled'],
            'calculated' => ['applied', 'cancelled'],
            'applied' => ['draft'],
        ];

        DB::transaction(function () use ($retaceo, $nuevoEstado, $transitions) {
            // Primero la compra y luego el retaceo, en el mismo orden que al crearlo. Con la compra
            // bloqueada, aplicar el costo no se cruza con completarla, que también fija el costo
            $purchase = Purchase::queryAllBranches()->whereKey($retaceo->id_purchase)->lockForUpdate()->firstOrFail();

            // Con el estado actual: si otro usuario lo canceló, ya no se aplica
            $retaceo->refreshWithLock();

            if (! in_array($nuevoEstado, $transitions[$retaceo->status] ?? [], true)) {
                throw new InvalidArgumentException(sprintf(
                    'Un retaceo %s no puede pasar a %s.',
                    mb_strtolower(Retaceo::STATUS_LABELS[$retaceo->status] ?? $retaceo->status),
                    mb_strtolower(Retaceo::STATUS_LABELS[$nuevoEstado] ?? $nuevoEstado)
                ));
            }

            $revierte = $retaceo->status === 'applied';

            // Antes de cambiarlo: si un lote ya se colocó, no se revierte
            if ($revierte) {
                $this->inventario->anularLotes($purchase);
            }

            $retaceo->update(['status' => $nuevoEstado]);

            // Al aplicarlo, su costo puesto en bodega pasa a ser el costo de los productos y de
            // los lotes de la compra, que se crean por ubicar. Al revertirlo, una compra
            // completada vuelve al costo de su factura
            if ($nuevoEstado === 'applied' || $revierte) {
                $this->costos->actualizarDesdeCompra($purchase);
            }

            if ($nuevoEstado === 'applied') {
                $this->inventario->crearLotes($purchase);
            }
        });
    }

    /**
     * Elimina un retaceo en borrador.
     */
    public function eliminar(Retaceo $retaceo): void
    {
        DB::transaction(function () use ($retaceo) {
            if ($retaceo->refreshWithLock()->status !== 'draft') {
                throw new InvalidArgumentException('Solo se pueden eliminar retaceos en borrador.');
            }

            $retaceo->delete();
        });
    }
}
