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
        'department_id',
        'municipality_id',
        'district_id',
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

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function municipality()
    {
        return $this->belongsTo(Municipality::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }
}