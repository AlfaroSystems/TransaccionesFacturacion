<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\RefreshesWithLock;
use App\Models\Scopes\BranchScope;
use App\Support\BranchAccess;
use App\Support\DocumentSequence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Traslado de lotes entre bodegas: Casa Matriz reparte a las sucursales lo que entró con las
 * compras. Borrador → Despachado (sale de la bodega origen) → Recibido (entra a la destino).
 * Ver InventoryTransferService.
 */
class InventoryTransfer extends Model
{
    use BelongsToBranch, RefreshesWithLock;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_DISPATCHED = 'dispatched';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Borrador',
        self::STATUS_DISPATCHED => 'Despachado',
        self::STATUS_RECEIVED => 'Recibido',
        self::STATUS_CANCELLED => 'Cancelado',
    ];

    const UPDATED_AT = null;

    protected $table = 'inventory_transfers';

    protected $primaryKey = 'id_inventory_transfer';

    protected $fillable = [
        'id_source_warehouse',
        'id_destination_warehouse',
        'id_vehicle_location',
        'transfer_date',
        'dispatched_at',
        'received_at',
        'status',
        'id_user',
        'notes',
    ];

    protected $attributes = [
        'status' => self::STATUS_DRAFT,
    ];

    protected $casts = [
        'transfer_date' => 'datetime',
        'dispatched_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (InventoryTransfer $transfer) {
            $transfer->transfer_code ??= DocumentSequence::next('TRF-'.now()->year.'-', 'inventory_transfers', 'transfer_code');
        });
    }

    /**
     * Visible si sale o llega a una bodega de la sucursal del usuario (de toda su empresa,
     * para el departamento de compras).
     */
    public function restrictToBranch(Builder $query, int $branchId): void
    {
        $branches = BranchAccess::isPurchasingDepartment() ? BranchAccess::companyBranchIds() : [$branchId];
        $warehouses = Warehouse::queryAllBranches()->whereIn('id_branch', $branches)->select('id_warehouse');

        $query->where(fn (Builder $q) => $q
            ->whereIn($this->qualifyColumn('id_source_warehouse'), $warehouses)
            ->orWhereIn($this->qualifyColumn('id_destination_warehouse'), $warehouses));
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function details(): HasMany
    {
        return $this->hasMany(InventoryTransferDetail::class, 'id_inventory_transfer', 'id_inventory_transfer');
    }

    public function sourceWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'id_source_warehouse', 'id_warehouse')
            ->withoutGlobalScope(BranchScope::class);
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'id_destination_warehouse', 'id_warehouse')
            ->withoutGlobalScope(BranchScope::class);
    }

    public function vehicleLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'id_vehicle_location', 'id_location')
            ->withoutGlobalScope(BranchScope::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}
