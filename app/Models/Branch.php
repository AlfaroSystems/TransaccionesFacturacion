<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Branch extends Model
{
    use HasFactory;

    // Campos que se pueden guardar en la tabla branches
    protected $fillable = [
        'company_id',
        'name',
        'address',
        'phone',
        'email',
        'description',
        'is_active'
    ];

    // Una sucursal pertenece a una empresa
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Una sucursal tiene muchas bodegas
    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }
}
