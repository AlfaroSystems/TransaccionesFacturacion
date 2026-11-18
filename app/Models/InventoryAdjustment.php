<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ajuste de inventario por conteo físico: lo contado de un lote en una ubicación de bodega. La
 * diferencia con lo que decía el sistema entró o salió al costo del lote (su movimiento del
 * kardex lleva este ajuste como documento).
 */
class InventoryAdjustment extends Model
{
    public const REASON_COUNT = 'count';

    public const REASON_DAMAGE = 'damage';

    public const REASON_EXPIRED = 'expired';

    public const REASON_LOSS = 'loss';

    public const REASON_OTHER = 'other';

    public const REASON_LABELS = [
        self::REASON_COUNT => 'Diferencia de conteo',
        self::REASON_DAMAGE => 'Daño',
        self::REASON_EXPIRED => 'Vencimiento',
        self::REASON_LOSS => 'Pérdida o robo',
        self::REASON_OTHER => 'Otro',
    ];

    const UPDATED_AT = null;

    protected $table = 'inventory_adjustments';

    protected $primaryKey = 'id_inventory_adjustment';

    protected $fillable = [
        'adjustment_code',
        'id_inventory_lot',
        'id_product',
        'id_warehouse',
        'id_inventory_location',
        'quantity_before',
        'quantity_counted',
        'difference',
        'unit_cost',
        'reason',
        'notes',
        'id_user',
    ];

    protected $casts = [
        'quantity_before' => 'decimal:4',
        'quantity_counted' => 'decimal:4',
        'difference' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'created_at' => 'datetime',
    ];

    public function reasonLabel(): string
    {
        return self::REASON_LABELS[$this->reason] ?? $this->reason;
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(InventoryLot::class, 'id_inventory_lot', 'id_inventory_lot')
            ->withoutGlobalScope(BranchScope::class);
    }

    public function inventoryLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'id_inventory_location', 'id_inventory_location');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'id_warehouse', 'id_warehouse')
            ->withoutGlobalScope(BranchScope::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}
