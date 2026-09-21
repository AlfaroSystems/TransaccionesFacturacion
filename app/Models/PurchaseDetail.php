<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseDetail extends Model
{
    use HasFactory;

    protected $table = 'purchase_details';
    protected $primaryKey = 'id_purchase_detail';

    protected $fillable = [
        'id_purchase',
        'id_purchase_order_detail',
        'id_product',
        'quantity_ordered',
        'quantity_received',
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
        'quantity_ordered' => 'decimal:4',
        'quantity_received' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'discount' => 'decimal:4',
        'subtotal' => 'decimal:4',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:4',
        'total' => 'decimal:4',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(
            Purchase::class,
            'id_purchase',
            'id_purchase'
        );
    }

    public function purchaseOrderDetail(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseOrderDetail::class,
            'id_purchase_order_detail',
            'id_purchase_order_detail'
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

    public function retaceoDetails(): HasMany
    {
        return $this->hasMany(
            RetaceoDetail::class,
            'id_purchase_detail',
            'id_purchase_detail'
        );
    }
}
