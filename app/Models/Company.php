<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'commercial_name',
        'nit',
        'nrc',
        'commercial_line_1',
        'commercial_line_2',
        'commercial_line_3',
        'address',
        'department_id',
        'municipality_id',
        'district_id',
        'phone',
        'email',
        'web_site',
        'logo',
        'is_active',
    ];

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function municipality()
    {
        return $this->belongsTo(Municipality::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }
}