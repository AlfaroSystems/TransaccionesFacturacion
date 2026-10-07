<?php

namespace App\Models;
use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Branch extends Model
{
    use HasFactory, BelongsToBranch;

    // Un usuario sin restricción ve todas las sucursales; los demás, solo la suya
    public function restrictToBranch(Builder $query, int $branchId): void
    {
        $query->whereKey($branchId);
    }

    protected $primaryKey = 'id_branch';

    // Campos que se pueden guardar en la tabla branches
    protected $fillable = [
        'id_company',
        'name',
        'addres',
        'id_department',
        'id_municipality',
        'id_district',
        'phone',
        'email',
        'description',
        'is_active',
        'is_purchasing_department',
    ];

    protected $casts = [
        'is_purchasing_department' => 'boolean',
    ];

    // Una sucursal pertenece a una empresa
    public function company()
    {
        return $this->belongsTo(Company::class, 'id_company');
    }

    // Una sucursal tiene muchas bodegas
    public function warehouses()
    {
        return $this->hasMany(Warehouse::class, 'id_branch');
    }

    // Una sucursal tiene muchos usuarios asignados
    public function users()
    {
        return $this->hasMany(User::class, 'id_branch');
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