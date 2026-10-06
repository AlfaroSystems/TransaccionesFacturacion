<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WarehouseCategory extends Model
{
    use HasFactory;

    protected $table = 'warehouse_category';
    protected $primaryKey = 'id_warehouse_category';

    protected $fillable = [
        'name',
        'description',
        'is_active'
    ];

    public function warehouses()
    {
        return $this->hasMany(
            Warehouse::class,
            'id_warehouse_category'
        );
    }
}
