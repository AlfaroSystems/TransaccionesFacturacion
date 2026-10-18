<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Support\BranchAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Existencia, valor y costo promedio ponderado de un producto en una empresa. Lo mantiene
 * InventoryService con cada entrada, salida o ajuste de costo.
 */
class InventoryCostAverage extends Model
{
    use BelongsToBranch;

    // Solo los de la empresa del usuario
    public function restrictToBranch(Builder $query, int $branchId): void
    {
        $query->where($this->qualifyColumn('id_company'), BranchAccess::companyId());
    }

    const CREATED_AT = null;

    protected $table = 'inventory_cost_averages';

    protected $primaryKey = 'id_inventory_cost_average';

    protected $fillable = [
        'id_company',
        'id_product',
        'quantity_on_hand',
        'inventory_value',
        'average_unit_cost',
    ];

    protected $casts = [
        'quantity_on_hand' => 'decimal:4',
        'inventory_value' => 'decimal:4',
        'average_unit_cost' => 'decimal:4',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'id_company', 'id_company');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }
}
