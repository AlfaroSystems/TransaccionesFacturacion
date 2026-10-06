<?php

namespace App\Models;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Company extends Model
{
    use HasFactory, BelongsToBranch;

    // Solo la empresa a la que pertenece la sucursal del usuario
    public function restrictToBranch(Builder $query, int $branchId): void
    {
        $query->whereHas('branches');
    }

    protected $primaryKey = 'id_company';

    protected $fillable = [
        'name',
        'commercial_name',
        'nit',
        'nrc',
        'commercial_line_1',
        'commercial_line_2',
        'commercial_line_3',
        'addres',
        'id_department',
        'id_municipality',
        'id_district',
        'phone',
        'email',
        'web_site',
        'logo',
        'is_active',
    ];

    public function branches()
    {
        return $this->hasMany(Branch::class, 'id_company');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'id_department');
    }

    public function municipality()
    {
        return $this->belongsTo(Municipality::class, 'id_municipality');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'id_district');
    }
}