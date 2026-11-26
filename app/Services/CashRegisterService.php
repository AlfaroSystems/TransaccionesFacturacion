<?php

namespace App\Services;

use App\Models\CashOutflow;
use App\Models\CashShift;
use App\Models\Sale;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Turnos de caja: abrir, registrar egresos, acumular ventas y cerrar con arqueo.
 */
class CashRegisterService
{
    /** Abre un turno de caja en la sucursal. Falla si ya hay uno abierto. */
    public function abrir(int $branchId, float $openingAmount): CashShift
    {
        return DB::transaction(function () use ($branchId, $openingAmount) {
            if (CashShift::openForBranch($branchId)) {
                throw ValidationException::withMessages(['opening_amount' => 'Ya hay un turno de caja abierto en la sucursal.']);
            }

            if ($openingAmount < 0) {
                throw ValidationException::withMessages(['opening_amount' => 'El monto de apertura no puede ser negativo.']);
            }

            return CashShift::create([
                'id_branch' => $branchId,
                'id_user' => Auth::id(),
                'opened_at' => now(),
                'opening_amount' => round($openingAmount, 2),
                'status' => CashShift::STATUS_OPEN,
            ]);
        });
    }

    /** Registra un egreso de efectivo del turno y actualiza el total de egresos. */
    public function egreso(CashShift $shift, float $amount, string $concept): CashOutflow
    {
        return DB::transaction(function () use ($shift, $amount, $concept) {
            $shift = CashShift::queryAllBranches()->whereKey($shift->id_cash_shift)->lockForUpdate()->firstOrFail();

            if (! $shift->isOpen()) {
                throw ValidationException::withMessages(['amount' => 'El turno de caja ya está cerrado.']);
            }

            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'El monto del egreso debe ser mayor que cero.']);
            }

            $outflow = CashOutflow::create([
                'id_cash_shift' => $shift->id_cash_shift,
                'amount' => round($amount, 2),
                'concept' => $concept,
                'id_user' => Auth::id(),
            ]);

            $shift->update(['total_outflows' => round((float) $shift->total_outflows + $amount, 2)]);

            return $outflow;
        });
    }

    /** Suma la venta al turno: total y total por forma de pago. Se llama dentro de la venta. */
    public function acumularVenta(int $shiftId, Sale $sale): void
    {
        $shift = CashShift::queryAllBranches()->whereKey($shiftId)->lockForUpdate()->firstOrFail();

        $campoPago = match ($sale->payment_method) {
            'cash' => 'total_cash',
            'credit_card' => 'total_card',
            'check' => 'total_check',
            default => null,
        };

        $datos = ['total_sales' => round((float) $shift->total_sales + (float) $sale->total, 2)];

        if ($campoPago) {
            $datos[$campoPago] = round((float) $shift->{$campoPago} + (float) $sale->total, 2);
        }

        $shift->update($datos);
    }

    /** Cierra el turno: registra lo contado y la diferencia contra el efectivo esperado. */
    public function cerrar(CashShift $shift, float $countedAmount, ?string $notes = null): CashShift
    {
        return DB::transaction(function () use ($shift, $countedAmount, $notes) {
            $shift = CashShift::queryAllBranches()->whereKey($shift->id_cash_shift)->lockForUpdate()->firstOrFail();

            if (! $shift->isOpen()) {
                throw ValidationException::withMessages(['counted_amount' => 'El turno de caja ya está cerrado.']);
            }

            if ($countedAmount < 0) {
                throw ValidationException::withMessages(['counted_amount' => 'Lo contado no puede ser negativo.']);
            }

            $countedAmount = round($countedAmount, 2);

            $shift->update([
                'closed_at' => now(),
                'counted_amount' => $countedAmount,
                'difference' => round($countedAmount - $shift->expectedCash(), 2),
                'status' => CashShift::STATUS_CLOSED,
                'id_closed_by' => Auth::id(),
                'notes' => $notes,
            ]);

            return $shift;
        });
    }
}
