<?php

namespace App\Models;
use App\Models\Concerns\BelongsToBranch;
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
        return $this->belongsTo(
            Branch::class,
            'id_branch'
        );
    }

    public function warehouseCategory()
    {
        return $this->belongsTo(
            WarehouseCategory::class,
            'id_warehouse_category'
        );
    }
}
