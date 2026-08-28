<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SupplierContact extends Model
{
    protected $primaryKey = 'id_contact';

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