<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    // Campos que se pueden guardar en la tabla branches
    protected $fillable = [
        'company_id',
        'name',
        'address',
        'phone',
        'email',
        'is_active'
    ];

    // Una sucursal pertenece a una empresa
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
} 