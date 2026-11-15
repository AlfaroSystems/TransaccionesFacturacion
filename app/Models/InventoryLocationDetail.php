<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Scopes\BranchScope;
use App\Support\BranchAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Existencia actual de un lote en una ubicación de inventario. Cada movimiento la suma o resta
 * al crearse (ver InventoryMovement), y la fila se borra cuando queda en cero: aquí solo está
 * lo que hay. El historial sigue en inventory_movements.
 */
class InventoryLocationDetail extends Model
{
    use BelongsToBranch;

    // La existencia de las bodegas visibles: las de la sucursal del usuario, o las de toda su
    // empresa para el departamento de compras
    public function restrictToBranch(Builder $query, int $branchId): void
    {
        $branches = BranchAccess::isPurchasingDepartment() ? BranchAccess::companyBranchIds() : [$branchId];

        $query->whereIn(
            $this->qualifyColumn('id_inventory_location'),
            InventoryLocation::query()
                ->inWarehouses(Warehouse::queryAllBranches()->whereIn('id_branch', $branches)->select('id_warehouse'))
                ->select('id_inventory_location')
        );
    }

    const CREATED_AT = null;

    protected $table = 'inventory_location_details';

    protected $primaryKey = 'id_inventory_location_detail';

    protected $fillable = [
        'id_inventory_lot',
        'id_inventory_location',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    /**
     * Suma (o resta, con cantidad negativa) existencia del lote en la ubicación. Cada caso es
     * una sola sentencia: dos movimientos a la vez no se pisan.
     */
    public static function accumulate(int $lotId, int $inventoryLocationId, float $quantity): void
    {
        $quantity = round($quantity, 4);

        if (abs($quantity) < 0.00001) {
            return;
        }

        // Una entrada crea la fila o la suma. Una salida solo resta de la que existe: PostgreSQL
        // revisa "cantidad >= 0" en la fila propuesta del INSERT antes de ver el conflicto, así
        // que una salida no puede ir por ese camino
        if ($quantity > 0) {
            DB::statement(
                'INSERT INTO inventory_location_details (id_inventory_lot, id_inventory_location, quantity, updated_at)
                 VALUES (?, ?, ?, NOW())
                 ON CONFLICT (id_inventory_lot, id_inventory_location)
                 DO UPDATE SET quantity = inventory_location_details.quantity + EXCLUDED.quantity, updated_at = NOW()',
                [$lotId, $inventoryLocationId, $quantity]
            );

            return;
        }

        $restadas = DB::update(
            'UPDATE inventory_location_details SET quantity = quantity + ?, updated_at = NOW()
             WHERE id_inventory_lot = ? AND id_inventory_location = ?',
            [$quantity, $lotId, $inventoryLocationId]
        );

        if ($restadas === 0) {
            throw new RuntimeException("No hay existencia del lote {$lotId} en la ubicación de inventario {$inventoryLocationId}: no puede salir nada de ahí.");
        }

        DB::table('inventory_location_details')
            ->where('id_inventory_lot', $lotId)
            ->where('id_inventory_location', $inventoryLocationId)
            ->where('quantity', 0)
            ->delete();
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

    /**
     * Ubicación de bodega (si la existencia está en una bodega y no en un vehículo).
     */
    public function location(): HasOneThrough
    {
        return $this->hasOneThrough(Location::class, InventoryLocation::class, 'id_inventory_location', 'id_location', 'id_inventory_location', 'id_location')
            ->withoutGlobalScope(BranchScope::class);
    }
}
