<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\RefreshesWithLock;
use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lote de inventario: lo recibido de una línea de compra, con el costo de su retaceo. Nace por
 * ubicar al aplicarse el retaceo (uno por línea; mientras está por ubicar se puede dividir y
 * anotarle el lote del proveedor y el vencimiento); al confirmar su colocación entra al
 * inventario. Dónde está y cuánto queda se calcula con sus movimientos (ver InventoryService).
 */
class InventoryLot extends Model
{
    use BelongsToBranch, RefreshesWithLock;

    public const STATUS_PENDING = 'pending_placement';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Por ubicar',
        self::STATUS_ACTIVE => 'Disponible',
        self::STATUS_CANCELLED => 'Anulado',
    ];

    // Visible si llegó a una bodega visible para el usuario, o si tiene movimientos en alguna
    // (lo que se trasladó a su sucursal)
    public function restrictToBranch(Builder $query, int $branchId): void
    {
        $query->where(fn ($q) => $q->whereHas('warehouse')->orWhereHas('movements'));
    }

    const UPDATED_AT = null;

    protected $table = 'inventory_lots';

    protected $primaryKey = 'id_inventory_lot';

    protected $fillable = [
        'lot_code',
        'id_product',
        'id_purchase_detail',
        'id_warehouse',
        'received_at',
        'quantity_received',
        'unit_cost',
        'total_cost',
        'status',
        'placed_at',
        'supplier_lot_number',
        'expiration_date',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'placed_at' => 'datetime',
        'expiration_date' => 'date',
        'quantity_received' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
    ];

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

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

    /**
     * Bodega a la que llegó (la de su compra).
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'id_warehouse', 'id_warehouse');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'id_inventory_lot', 'id_inventory_lot');
    }

    /**
     * Reparto en las ubicaciones de su bodega (el que se confirma al colocarlo).
     */
    public function placements(): HasMany
    {
        return $this->hasMany(InventoryLotPlacement::class, 'id_inventory_lot', 'id_inventory_lot');
    }

    public function labelPrints(): HasMany
    {
        return $this->hasMany(InventoryLabelPrint::class, 'id_inventory_lot', 'id_inventory_lot');
    }

    /**
     * La bodega sin el filtro por sucursal: para mostrarla a quien ve el lote por sus movimientos.
     */
    public function warehouseAllBranches(): BelongsTo
    {
        return $this->warehouse()->withoutGlobalScope(BranchScope::class);
    }
}
