<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Branch extends Model
{

    use HasFactory;


    protected $fillable = [

        'name',
        'description',
        'is_active'

    ];



    public function warehouses()
    {

        return $this->hasMany(
            Warehouse::class
        );

    }


}