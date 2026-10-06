<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Municipality extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_municipality';

    protected $fillable = [
        'id_department',
        'code',
        'name',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'id_department');
    }

    public function districts()
    {
        return $this->hasMany(District::class, 'id_municipality');
    }
}
