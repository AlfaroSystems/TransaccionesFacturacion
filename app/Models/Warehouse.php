<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Warehouse extends Model
{

    use HasFactory;


    protected $fillable = [

        'branch_id',
        'warehouse_category_id',
        'name',
        'description',
        'is_active'

    ];



    public function branch()
    {

        return $this->belongsTo(
            Branch::class
        );

    }



    public function warehouseCategory()
    {

        return $this->belongsTo(
            WarehouseCategory::class,
            'warehouse_category_id'
        );

    }


}