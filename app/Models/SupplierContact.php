<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SupplierContact extends Model
{
    // Nombres de tabla y llave según el diagrama del sistema
    protected $table = 'supliers_contacts';
    protected $primaryKey = 'id_suplier_contact';

    protected $fillable = [
        'id_supplier',
        'full_name',
        'phone',
        'email',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function supplier()
    {
        return $this->belongsTo(
            Supplier::class,
            'id_supplier'
        );
    }
}