<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $primaryKey = 'id_supplier';

    protected $fillable = [
        'code',
        'name',
        'email',
        'phone',
        'country',
        'address',
        'website',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];


    public function contacts()
    {
        return $this->hasMany(
            SupplierContact::class,
            'id_supplier'
        );
    }
} //

