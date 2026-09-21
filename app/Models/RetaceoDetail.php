<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetaceoDetail extends Model
{
    use HasFactory;

    protected $table = 'retaceo_details';
    protected $primaryKey = 'id_retaceo_detail';

    protected $fillable = [
        'id_retaceo',
        'id_purchase_detail',
        'id_product',
        'quantity',
        'cost_fob',
        'freight_amount',
        'expense_amount',
        'dai_amount',
        'unit_cost',
        'total_cost',
    ];

    protected $casts = [
        'quantity'       => 'decimal:4',
        'cost_fob'       => 'decimal:4',
        'freight_amount' => 'decimal:4',
        'expense_amount' => 'decimal:4',
        'dai_amount'     => 'decimal:4',
        'unit_cost'      => 'decimal:4',
        'total_cost'     => 'decimal:4',
    ];

    public function retaceo(): BelongsTo
    {
        return $this->belongsTo(
            Retaceo::class,
            'id_retaceo',
            'id_retaceo'
        );
    }

    public function purchaseDetail(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseDetail::class,
            'id_purchase_detail',
            'id_purchase_detail'
        );
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(
            Product::class,
            'id_product',
            'id'
        );
    }
}
