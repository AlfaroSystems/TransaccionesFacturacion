<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use HasFactory;

    protected $table = 'units';

    protected $fillable = [
        'name',
        'abbreviation',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Productos donde esta unidad se utiliza como unidad de compra.
     */
    public function purchaseProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'purchase_unit');
    }

    /**
     * Productos donde esta unidad se utiliza como unidad de venta.
     */
    public function saleProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'sale_unit');
    }
}
