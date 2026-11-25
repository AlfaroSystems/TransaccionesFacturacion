<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un precio de venta de un producto, vigente desde valid_from hasta valid_until. El precio
 * vigente es el que tiene valid_until en NULL. Los registros no se editan ni se borran: cada
 * cambio cierra el vigente y abre uno nuevo (ver PriceService).
 */
class ProductPrice extends Model
{
    const UPDATED_AT = null;

    protected $table = 'product_prices';

    protected $primaryKey = 'id_product_price';

    protected $fillable = [
        'id_product',
        'price',
        'currency',
        'valid_from',
        'valid_until',
        'id_user',
        'notes',
    ];

    protected $casts = [
        'price' => 'decimal:4',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
    ];

    public function isCurrent(): bool
    {
        return $this->valid_until === null;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}
