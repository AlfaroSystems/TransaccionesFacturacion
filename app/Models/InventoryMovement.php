<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Scopes\BranchScope;
use App\Support\BranchAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

/**
 * Entrada o salida de un lote en una bodega y ubicación de inventario. La existencia es la suma
 * de quantity_in - quantity_out; el valor, la suma de total_cost con signo (ver signedValueSql).
 * Los ajustes de costo no mueven cantidad: solo cambian el valor.
 *
 * Al crearse, cada movimiento suma o resta su cantidad a la existencia del lote en su ubicación
 * (inventory_location_details). Los movimientos no se editan ni se borran.
 */
class InventoryMovement extends Model
{
    use BelongsToBranch;

    public const TYPE_PURCHASE_RECEIPT = 'purchase_receipt';

    public const TYPE_PURCHASE_CANCEL = 'purchase_cancel';

    public const TYPE_COST_ADJUSTMENT = 'cost_adjustment';

    public const TYPE_TRANSFER_OUT = 'transfer_out';

    public const TYPE_TRANSFER_IN = 'transfer_in';

    public const TYPE_TRANSFER_LOSS = 'transfer_loss';

    public const TYPE_TRANSFER_LOAD = 'transfer_load';

    public const TYPE_TRANSFER_UNLOAD = 'transfer_unload';

    public const TYPE_TRANSFER_DAMAGE = 'transfer_damage';

    // Sobrante de un traslado: salió de la bodega origen sin registrarse (sale de ahí y entra a
    // la destino); lo que la origen ya no tiene entra a la destino como ganancia
    public const TYPE_TRANSFER_SURPLUS = 'transfer_surplus';

    public const TYPE_TRANSFER_SURPLUS_OUT = 'transfer_surplus_out';

    public const TYPE_TRANSFER_SURPLUS_GAIN = 'transfer_surplus_gain';

    // Ajuste por conteo físico (InventoryAdjustment): la diferencia entra o sale al costo del lote
    public const TYPE_ADJUSTMENT_IN = 'adjustment_in';

    public const TYPE_ADJUSTMENT_OUT = 'adjustment_out';

    public const TYPE_RELOCATION_OUT = 'relocation_out';

    public const TYPE_RELOCATION_IN = 'relocation_in';

    // Anulación de compra y ajuste de costo quedan para el historial: los lotes ya nacen con el
    // costo del retaceo y una compra con lotes no se anula
    public const TYPE_LABELS = [
        self::TYPE_PURCHASE_RECEIPT => 'Entrada por compra',
        self::TYPE_PURCHASE_CANCEL => 'Anulación de compra',
        self::TYPE_COST_ADJUSTMENT => 'Ajuste de costo',
        self::TYPE_TRANSFER_OUT => 'Salida por traslado',
        self::TYPE_TRANSFER_LOAD => 'Carga al vehículo',
        self::TYPE_TRANSFER_UNLOAD => 'Descarga del vehículo',
        self::TYPE_TRANSFER_IN => 'Entrada por traslado',
        self::TYPE_TRANSFER_LOSS => 'Faltante en traslado',
        self::TYPE_TRANSFER_DAMAGE => 'Baja por daño en traslado',
        self::TYPE_TRANSFER_SURPLUS_OUT => 'Sobrante: sale de la bodega origen',
        self::TYPE_TRANSFER_SURPLUS => 'Sobrante en traslado',
        self::TYPE_TRANSFER_SURPLUS_GAIN => 'Ganancia por sobrante (sin origen)',
        self::TYPE_ADJUSTMENT_IN => 'Ajuste de inventario (entra)',
        self::TYPE_ADJUSTMENT_OUT => 'Ajuste de inventario (sale)',
        self::TYPE_RELOCATION_OUT => 'Cambio de ubicación (sale)',
        self::TYPE_RELOCATION_IN => 'Cambio de ubicación (entra)',
    ];

    public const REFERENCE_PURCHASE = 'purchase';

    public const REFERENCE_TRANSFER = 'transfer';

    // Los cambios de ubicación no tienen documento: el par salida/entrada se reconoce por el lote
    public const REFERENCE_RELOCATION = 'relocation';

    public const REFERENCE_ADJUSTMENT = 'adjustment';

    // Movimientos de las bodegas visibles: las de la sucursal del usuario, o las de toda su
    // empresa para el departamento de compras
    public function restrictToBranch(Builder $query, int $branchId): void
    {
        $branches = BranchAccess::isPurchasingDepartment() ? BranchAccess::companyBranchIds() : [$branchId];

        $query->whereIn(
            $this->qualifyColumn('id_warehouse'),
            Warehouse::queryAllBranches()->whereIn('id_branch', $branches)->select('id_warehouse')
        );
    }

    /**
     * Valor del movimiento con signo: las salidas restan; las entradas y los ajustes de
     * costo (ya con su signo) suman.
     */
    public static function signedValueSql(): string
    {
        return 'CASE WHEN quantity_out > 0 THEN -total_cost ELSE total_cost END';
    }

    const UPDATED_AT = null;

    protected $table = 'inventory_movements';

    protected $primaryKey = 'id_inventory_movement';

    protected $fillable = [
        'id_product',
        'id_inventory_lot',
        'id_warehouse',
        'id_inventory_location',
        'movement_type',
        'movement_date',
        'quantity_in',
        'quantity_out',
        'unit_cost',
        'total_cost',
        'reference_type',
        'reference_id',
        'id_user',
    ];

    protected $casts = [
        'movement_date' => 'datetime',
        'quantity_in' => 'decimal:4',
        'quantity_out' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
    ];

    protected static function booted(): void
    {
        static::created(function (InventoryMovement $movement) {
            if ($movement->id_inventory_lot && $movement->id_inventory_location) {
                InventoryLocationDetail::accumulate(
                    (int) $movement->id_inventory_lot,
                    (int) $movement->id_inventory_location,
                    (float) $movement->quantity_in - (float) $movement->quantity_out
                );
            }
        });
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->movement_type] ?? $this->movement_type;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(InventoryLot::class, 'id_inventory_lot', 'id_inventory_lot')
            ->withoutGlobalScope(BranchScope::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'id_warehouse', 'id_warehouse')
            ->withoutGlobalScope(BranchScope::class);
    }

    public function inventoryLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'id_inventory_location', 'id_inventory_location');
    }

    /**
     * Ubicación de bodega del movimiento (a través de su ubicación de inventario).
     */
    public function location(): HasOneThrough
    {
        return $this->hasOneThrough(Location::class, InventoryLocation::class, 'id_inventory_location', 'id_location', 'id_inventory_location', 'id_location')
            ->withoutGlobalScope(BranchScope::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}
