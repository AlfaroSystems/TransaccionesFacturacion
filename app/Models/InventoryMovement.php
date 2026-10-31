<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Scopes\BranchScope;
use App\Support\BranchAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entrada o salida de un lote en una bodega y ubicación. La existencia es la suma de
 * quantity_in - quantity_out; el valor, la suma de total_cost con signo (ver signedValueSql).
 * Los ajustes de costo no mueven cantidad: solo cambian el valor.
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

    public const TYPE_RELOCATION_OUT = 'relocation_out';

    public const TYPE_RELOCATION_IN = 'relocation_in';

    // Anulación de compra y ajuste de costo quedan para el historial: los lotes ya nacen con el
    // costo del retaceo y una compra con lotes no se anula
    public const TYPE_LABELS = [
        self::TYPE_PURCHASE_RECEIPT => 'Entrada por compra',
        self::TYPE_PURCHASE_CANCEL => 'Anulación de compra',
        self::TYPE_COST_ADJUSTMENT => 'Ajuste de costo',
        self::TYPE_TRANSFER_OUT => 'Salida por traslado',
        self::TYPE_TRANSFER_IN => 'Entrada por traslado',
        self::TYPE_TRANSFER_LOSS => 'Merma en traslado',
        self::TYPE_RELOCATION_OUT => 'Cambio de ubicación (sale)',
        self::TYPE_RELOCATION_IN => 'Cambio de ubicación (entra)',
    ];

    public const REFERENCE_PURCHASE = 'purchase';

    public const REFERENCE_TRANSFER = 'transfer';

    // Los cambios de ubicación no tienen documento: el par salida/entrada se reconoce por el lote
    public const REFERENCE_RELOCATION = 'relocation';

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
        'id_location',
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

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'id_location', 'id_location')
            ->withoutGlobalScope(BranchScope::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}
