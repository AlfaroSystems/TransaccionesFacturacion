<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

/**
 * Lote de un traslado: de qué ubicación de inventario sale, a cuál llega, y cuánto se pidió,
 * se despachó y se recibió. Se ve a través de su traslado, que es el que se filtra por sucursal.
 */
class InventoryTransferDetail extends Model
{
    public $timestamps = false;

    protected $table = 'inventory_transfer_details';

    protected $primaryKey = 'id_inventory_transfer_detail';

    protected $fillable = [
        'id_inventory_transfer',
        'id_inventory_lot',
        'id_product',
        'id_source_inventory_location',
        'id_destination_inventory_location',
        'quantity_requested',
        'quantity_dispatched',
        'quantity_received',
        'unit_cost',
        'notes',
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:4',
        'quantity_dispatched' => 'decimal:4',
        'quantity_received' => 'decimal:4',
        'unit_cost' => 'decimal:4',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(InventoryTransfer::class, 'id_inventory_transfer', 'id_inventory_transfer')
            ->withoutGlobalScope(BranchScope::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(InventoryLot::class, 'id_inventory_lot', 'id_inventory_lot')
            ->withoutGlobalScope(BranchScope::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }

    public function sourceInventoryLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'id_source_inventory_location', 'id_inventory_location');
    }

    public function destinationInventoryLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'id_destination_inventory_location', 'id_inventory_location');
    }

    /**
     * Ubicación de bodega de donde sale (a través de su ubicación de inventario).
     */
    public function sourceLocation(): HasOneThrough
    {
        return $this->hasOneThrough(Location::class, InventoryLocation::class, 'id_inventory_location', 'id_location', 'id_source_inventory_location', 'id_location')
            ->withoutGlobalScope(BranchScope::class);
    }

    /**
     * Ubicación de bodega a la que llega.
     */
    public function destinationLocation(): HasOneThrough
    {
        return $this->hasOneThrough(Location::class, InventoryLocation::class, 'id_inventory_location', 'id_location', 'id_destination_inventory_location', 'id_location')
            ->withoutGlobalScope(BranchScope::class);
    }
}
