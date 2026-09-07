<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderDetail extends Model
{
    protected $table = 'purchase_order_details';
    protected $primaryKey = 'id_purchase_order_detail';
    protected $fillable = [
        'id_purchase_order',
        'id_product',
        'quantity',
        'id_unit',
        'unit_price',
        'discount',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
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
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseOrder::class,
            'id_purchase_order',
            'id_purchase_order'
        );
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'id_unit');
    }
}