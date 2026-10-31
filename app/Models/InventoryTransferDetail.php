<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lote de un traslado: de qué ubicación sale, a cuál llega y cuánto se despachó y recibió.
 * Se ve a través de su traslado, que es el que se filtra por sucursal.
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
        'id_source_location',
        'id_destination_location',
        'quantity_dispatched',
        'quantity_received',
        'unit_cost',
        'notes',
    ];

    protected $casts = [
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

    public function sourceLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'id_source_location', 'id_location')
            ->withoutGlobalScope(BranchScope::class);
    }

    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'id_destination_location', 'id_location')
            ->withoutGlobalScope(BranchScope::class);
    }
}
