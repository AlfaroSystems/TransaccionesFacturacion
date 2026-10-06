<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Department extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_department';

    protected $fillable = [
        'code',
        'name',
        'short_name',
    ];

    public function municipalities()
    {
        return $this->hasMany(Municipality::class, 'id_department');
    }
}
