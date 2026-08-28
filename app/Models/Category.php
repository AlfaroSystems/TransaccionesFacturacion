<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Subcategorías pertenecientes a esta categoría.
     */
    public function subCategories(): HasMany
    {
        return $this->hasMany(SubCategory::class, 'id_category');
    }

    /**
     * Productos pertenecientes a esta categoría.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'id_category');
    }
}