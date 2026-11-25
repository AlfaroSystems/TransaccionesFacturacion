<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Consumo PEPS de un lote para cubrir una línea de venta: cantidad, costo del lote y el
 * movimiento de Kardex que registró la salida.
 */
class SaleCostDetail extends Model
{
    const UPDATED_AT = null;

    protected $table = 'sales_cost_details';

    protected $primaryKey = 'id_sales_cost_detail';

    protected $fillable = [
        'id_sale_detail',
        'id_inventory_lot',
        'id_inventory_location',
        'id_inventory_movement',
        'quantity',
        'unit_cost',
        'total_cost',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
    ];

    public function saleDetail(): BelongsTo
    {
        return $this->belongsTo(SaleDetail::class, 'id_sale_detail', 'id_sale_detail');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(InventoryLot::class, 'id_inventory_lot', 'id_inventory_lot');
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class, 'id_inventory_movement', 'id_inventory_movement');
    }
}
