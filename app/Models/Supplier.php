<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $primaryKey = 'id_supplier';


    protected $fillable = [
        'name',
        'email',
        'phone',
        'country',
        'website'
    ];


    public function contacts()
    {
        return $this->hasMany(
            SupplierContact::class,
            'id_supplier'
        );
    }
} //

