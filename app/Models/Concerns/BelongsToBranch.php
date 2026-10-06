<?php

namespace App\Models\Concerns;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Modelo cuyos registros pertenecen a una sucursal, directamente o a través de
 * un registro padre. Cada modelo define cómo se filtra en restrictToBranch().
 */
trait BelongsToBranch
{
    public static function bootBelongsToBranch(): void
    {
        static::addGlobalScope(new BranchScope);
    }

    /**
     * Restringe la consulta a los registros de la sucursal indicada.
     */
    abstract public function restrictToBranch(Builder $query, int $branchId): void;

    /**
     * Consulta sin el filtro por sucursal. Usar solo para operaciones internas que
     * deben considerar todas las sucursales, como los correlativos de documentos.
     */
    public static function queryAllBranches(): Builder
    {
        return static::withoutGlobalScope(BranchScope::class);
    }
}
