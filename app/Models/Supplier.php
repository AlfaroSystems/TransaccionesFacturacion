<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    // Nombre de la tabla según el diagrama del sistema
    protected $table = 'supliers';
    protected $primaryKey = 'id_supplier';

    protected $fillable = [
        'code',
        'name',
        'email',
        'phone',
        'country',
        'address',
        'id_department',
        'id_municipality',
        'id_district',
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
        return $this->belongsTo(Department::class, 'id_department');
    }

    public function municipality()
    {
        return $this->belongsTo(Municipality::class, 'id_municipality');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'id_district');
    }
}
