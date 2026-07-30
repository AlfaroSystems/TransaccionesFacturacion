<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class District extends Model
{
    use HasFactory;

    protected $fillable = [
        'municipality_id',
        'code',
        'name',
    ];

    public function municipality()
    {
        return $this->belongsTo(Municipality::class);
    }
}
