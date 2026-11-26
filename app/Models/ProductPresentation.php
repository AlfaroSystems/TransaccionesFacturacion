<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Presentación de venta de un producto (Unidad, Blíster, Caja...). factor = cuántas unidades
 * base del inventario contiene. El precio es por presentación (ver product_prices).
 */
class ProductPresentation extends Model
{
    const UPDATED_AT = null;

    protected $table = 'product_presentations';

    protected $primaryKey = 'id_product_presentation';

    protected $fillable = [
        'id_product',
        'name',
        'factor',
        'is_active',
    ];

    protected $casts = [
        'factor' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }

    /** Historial de precios de esta presentación (más reciente primero). */
    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class, 'id_product_presentation', 'id_product_presentation')->orderByDesc('valid_from');
    }

    /** Precio vigente de la presentación (el que no tiene fecha de cambio). */
    public function currentPrice(): HasOne
    {
        return $this->hasOne(ProductPrice::class, 'id_product_presentation', 'id_product_presentation')->whereNull('valid_until');
    }

    /** Valor del precio vigente, o null si aún no tiene precio. */
    public function precioVigente(): ?float
    {
        $price = $this->relationLoaded('currentPrice')
            ? $this->getRelation('currentPrice')
            : $this->currentPrice()->first();

        return $price ? (float) $price->price : null;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
