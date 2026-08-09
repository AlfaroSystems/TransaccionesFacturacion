<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubCategory extends Model
{
    protected $fillable = [
        'id_category',
        'name',
        'description',
        'is_active'
    ];
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Una subcategoría pertenece a una categoría.
     */

    public function category()
    {
        return $this->belongsTo(Category::class, 'id_category', 'id_category');
    }
    /**
     * Una subcategoría puede tener muchos productos.
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'id_sub_category', 'id');
    }
}