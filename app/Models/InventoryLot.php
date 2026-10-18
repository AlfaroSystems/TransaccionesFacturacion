<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lote de inventario: lo recibido de una línea de compra, con su costo. Dónde está y cuánto
 * queda se calcula con sus movimientos (ver InventoryService).
 */
class InventoryLot extends Model
{
    use BelongsToBranch;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_LABELS = [
        self::STATUS_ACTIVE => 'Activo',
        self::STATUS_CANCELLED => 'Anulado',
    ];

    // Visible si tiene movimientos en alguna bodega visible para el usuario
    public function restrictToBranch(Builder $query, int $branchId): void
    {
        $query->whereHas('movements');
    }

    const UPDATED_AT = null;

    protected $table = 'inventory_lots';

    protected $primaryKey = 'id_inventory_lot';

    protected $fillable = [
        'lot_code',
        'id_product',
        'id_purchase_detail',
        'received_at',
        'quantity_received',
        'unit_cost',
        'total_cost',
        'status',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'quantity_received' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }

    /**
     * Línea de compra de la que entró.
     */
    public function purchaseDetail(): BelongsTo
    {
        return $this->belongsTo(PurchaseDetail::class, 'id_purchase_detail', 'id_purchase_detail');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'id_inventory_lot', 'id_inventory_lot');
    }
}
