<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Parte de un lote asignada a una ubicación de su bodega. Se ve a través de su lote, que es el
 * que se filtra por sucursal.
 */
class InventoryLotPlacement extends Model
{
    public $timestamps = false;

    protected $table = 'inventory_lot_placements';

    protected $primaryKey = 'id_inventory_lot_placement';

    protected $fillable = [
        'id_inventory_lot',
        'id_location',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    public function lot(): BelongsTo
    {
        return $this->belongsTo(InventoryLot::class, 'id_inventory_lot', 'id_inventory_lot')
            ->withoutGlobalScope(BranchScope::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'id_location', 'id_location')
            ->withoutGlobalScope(BranchScope::class);
    }
}
