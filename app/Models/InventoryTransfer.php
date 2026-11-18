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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Traslado de lotes entre bodegas: Casa Matriz reparte a las sucursales lo que entró con las
 * compras.
 *
 * Borrador → Por autorizar → Autorizado (o rechazado: vuelve a borrador con su motivo) →
 * En preparación → En tránsito (la mercadería va en el espacio de carga del vehículo) →
 * Recepción parcial (lo que falta sigue en el vehículo) → Con discrepancia → Recibido.
 * Antes de despacharse se puede cancelar. Ver InventoryTransferService.
 */
class InventoryTransfer extends Model
{
    use BelongsToBranch, RefreshesWithLock;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_AUTHORIZATION = 'pending_authorization';

    public const STATUS_AUTHORIZED = 'authorized';

    public const STATUS_PREPARING = 'preparing';

    public const STATUS_IN_TRANSIT = 'in_transit';

    public const STATUS_PARTIALLY_RECEIVED = 'partially_received';

    public const STATUS_WITH_DISCREPANCY = 'with_discrepancy';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Borrador',
        self::STATUS_PENDING_AUTHORIZATION => 'Por autorizar',
        self::STATUS_AUTHORIZED => 'Autorizado',
        self::STATUS_PREPARING => 'En preparación',
        self::STATUS_IN_TRANSIT => 'En tránsito',
        self::STATUS_PARTIALLY_RECEIVED => 'Recepción parcial',
        self::STATUS_WITH_DISCREPANCY => 'Con discrepancia',
        self::STATUS_RECEIVED => 'Recibido',
        self::STATUS_CANCELLED => 'Cancelado',
    ];

    // Se cancelan mientras su mercadería no haya salido de la bodega origen
    public const CANCELLABLE = [self::STATUS_DRAFT, self::STATUS_PENDING_AUTHORIZATION, self::STATUS_AUTHORIZED, self::STATUS_PREPARING];

    // Su mercadería (o parte) va en el vehículo: se recibe y se le registran discrepancias
    public const RECEIVABLE = [self::STATUS_IN_TRANSIT, self::STATUS_PARTIALLY_RECEIVED, self::STATUS_WITH_DISCREPANCY];

    // Autorizado y en preparación: lo solicitado queda apartado en la bodega origen hasta despacharse
    public const RESERVING = [self::STATUS_AUTHORIZED, self::STATUS_PREPARING];

    const UPDATED_AT = null;

    /**
     * Existencia apartada por los traslados autorizados o en preparación, por lote y ubicación
     * de origen ("lote|ubicación", como InventoryTransferService::clave), sin el traslado
     * indicado. Sin el filtro por sucursal: lo apartado vale para todos.
     *
     * @param  iterable<int>|null  $lotIds
     * @return Collection<string, float>
     */
    public static function apartado(?iterable $lotIds = null, ?int $excepto = null): Collection
    {
        return DB::table('inventory_transfer_details as d')
            ->join('inventory_transfers as t', 't.id_inventory_transfer', '=', 'd.id_inventory_transfer')
            ->whereIn('t.status', self::RESERVING)
            ->whereNotNull('d.id_source_inventory_location')
            ->when($excepto, fn ($q) => $q->where('t.id_inventory_transfer', '!=', $excepto))
            ->when($lotIds !== null, fn ($q) => $q->whereIn('d.id_inventory_lot', collect($lotIds)->all()))
            ->groupBy('d.id_inventory_lot', 'd.id_source_inventory_location')
            ->selectRaw('d.id_inventory_lot, d.id_source_inventory_location, SUM(d.quantity_requested) AS cantidad')
            ->get()
            ->mapWithKeys(fn ($fila) => [$fila->id_inventory_lot.'|'.$fila->id_source_inventory_location => (float) $fila->cantidad]);
    }

    protected $table = 'inventory_transfers';

    protected $primaryKey = 'id_inventory_transfer';

    protected $fillable = [
        'id_source_warehouse',
        'id_destination_warehouse',
        'id_vehicle',
        'id_driver_employee',
        'id_vehicle_location',
        'transfer_date',
        'authorized_at',
        'id_authorized_by',
        'status_reason',
        'prepared_at',
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
        'authorized_at' => 'datetime',
        'prepared_at' => 'datetime',
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

    public function isCancellable(): bool
    {
        return in_array($this->status, self::CANCELLABLE, true);
    }

    public function isReceivable(): bool
    {
        return in_array($this->status, self::RECEIVABLE, true);
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

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'id_vehicle', 'id_vehicle');
    }

    /**
     * Espacio de carga del vehículo donde viaja la mercadería (se elige al despachar).
     */
    public function vehicleLocation(): BelongsTo
    {
        return $this->belongsTo(VehicleLocation::class, 'id_vehicle_location', 'id_vehicle_location')
            ->withoutGlobalScope(BranchScope::class);
    }

    public function authorizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_authorized_by', 'id_user');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'id_driver_employee', 'id_employee')
            ->withoutGlobalScope(BranchScope::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}
