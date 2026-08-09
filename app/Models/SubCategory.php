<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubCategory extends Model
{
    use HasFactory;

    protected $table = 'sub_categories';

    protected $fillable = [
        'id_category',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Categoría padre a la que pertenece esta subcategoría.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'id_category');
    }

    /**
     * Productos pertenecientes a esta subcategoría.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'id_sub_category');
    }
}
