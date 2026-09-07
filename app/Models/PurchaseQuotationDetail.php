<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseQuotationDetail extends Model
{
    use HasFactory;

    protected $table = 'purchase_quotation_details';
    protected $primaryKey = 'id_purchase_quotation_detail';

    protected $fillable = [
        'id_purchase_quotation',
        'id_product',
        'quantity',
        'id_unit',
        'unit_price',
        'discount',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
        'delivery_days',
        'available_quantity',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'discount' => 'decimal:4',
        'subtotal' => 'decimal:4',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:4',
        'total' => 'decimal:4',
        'available_quantity' => 'decimal:4',
        'delivery_days' => 'integer',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseQuotation::class,
            'id_purchase_quotation',
            'id_purchase_quotation'
        );
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product', 'id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'id_unit', 'id');
    }
}
