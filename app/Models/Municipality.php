<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Municipality extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'code',
        'name',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function districts()
    {
        return $this->hasMany(District::class);
    }
}
