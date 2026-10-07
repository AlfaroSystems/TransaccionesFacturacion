<?php

namespace App\Models;
use App\Models\Concerns\BelongsToBranch;
use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Warehouse extends Model
{
    use HasFactory, BelongsToBranch;

    public function restrictToBranch(Builder $query, int $branchId): void
    {
        $query->where($this->qualifyColumn('id_branch'), $branchId);
    }

    protected $primaryKey = 'id_warehouse';

    protected $fillable = [
        'id_branch',
        'id_warehouse_category',
        'name',
        'description',
        'is_active'
    ];

    public function branch()
    {
        // Sin el filtro por sucursal: quien ve la bodega (p. ej. el departamento de
        // compras, de otra sucursal) debe ver también a qué sucursal pertenece
        return $this->belongsTo(
            Branch::class,
            'id_branch'
        )->withoutGlobalScope(BranchScope::class);
    }

    public function warehouseCategory()
    {
        return $this->belongsTo(
            WarehouseCategory::class,
            'id_warehouse_category'
        );
    }
}
