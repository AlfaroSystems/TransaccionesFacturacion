<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Support\BranchAccess;
use App\Support\DocumentSequence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Turno de caja (arqueo). Se abre con un monto; cada venta entregada suma su total y el total
 * por forma de pago; los egresos restan del efectivo esperado. Al cerrar se registra lo contado
 * y la diferencia. Un solo turno abierto por sucursal.
 */
class CashShift extends Model
{
    use BelongsToBranch;

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    public function restrictToBranch(Builder $query, int $branchId): void
    {
        $branches = BranchAccess::isPurchasingDepartment() ? BranchAccess::companyBranchIds() : [$branchId];

        $query->whereIn($this->qualifyColumn('id_branch'), $branches);
    }

    const UPDATED_AT = null;

    protected $table = 'cash_shifts';

    protected $primaryKey = 'id_cash_shift';

    protected $fillable = [
        'shift_code',
        'id_branch',
        'id_user',
        'opened_at',
        'closed_at',
        'opening_amount',
        'total_sales',
        'total_cash',
        'total_card',
        'total_check',
        'total_outflows',
        'counted_amount',
        'difference',
        'status',
        'id_closed_by',
        'notes',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_amount' => 'decimal:2',
        'total_sales' => 'decimal:2',
        'total_cash' => 'decimal:2',
        'total_card' => 'decimal:2',
        'total_check' => 'decimal:2',
        'total_outflows' => 'decimal:2',
        'counted_amount' => 'decimal:2',
        'difference' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (CashShift $shift) {
            if (empty($shift->shift_code)) {
                $shift->shift_code = DocumentSequence::next('CAJA-'.now()->year.'-', 'cash_shifts', 'shift_code', 6);
            }
        });
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    /** Efectivo que debería haber en caja: apertura + ventas en efectivo - egresos. */
    public function expectedCash(): float
    {
        return round((float) $this->opening_amount + (float) $this->total_cash - (float) $this->total_outflows, 2);
    }

    /** Turno abierto de una sucursal, o null. */
    public static function openForBranch(int $branchId): ?self
    {
        return static::queryAllBranches()
            ->where('id_branch', $branchId)
            ->where('status', self::STATUS_OPEN)
            ->first();
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'id_branch', 'id_branch');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_closed_by', 'id_user');
    }

    public function outflows(): HasMany
    {
        return $this->hasMany(CashOutflow::class, 'id_cash_shift', 'id_cash_shift');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'id_cash_shift', 'id_cash_shift');
    }
}
