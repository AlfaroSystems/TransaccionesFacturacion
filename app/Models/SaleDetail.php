<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Línea de una venta. unit_cost y total_cost (costo PEPS) se llenan al entregar; su desglose por
 * lote está en sales_cost_details.
 */
class SaleDetail extends Model
{
    public $timestamps = false;

    protected $table = 'sales_details';

    protected $primaryKey = 'id_sale_detail';

    protected $fillable = [
        'id_sale',
        'id_product',
        'id_product_presentation',
        'quantity',
        'id_unit',
        'unit_price',
        'discount',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
        'unit_cost',
        'total_cost',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'discount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'id_sale', 'id_sale');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }

    public function presentation(): BelongsTo
    {
        return $this->belongsTo(ProductPresentation::class, 'id_product_presentation', 'id_product_presentation');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'id_unit', 'id_unit');
    }

    public function costDetails(): HasMany
    {
        return $this->hasMany(SaleCostDetail::class, 'id_sale_detail', 'id_sale_detail');
    }
}
