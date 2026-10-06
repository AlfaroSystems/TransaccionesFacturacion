<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class District extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_district';

    protected $fillable = [
        'id_municipality',
        'code',
        'name',
    ];

    public function municipality()
    {
        return $this->belongsTo(Municipality::class, 'id_municipality');
    }
}
