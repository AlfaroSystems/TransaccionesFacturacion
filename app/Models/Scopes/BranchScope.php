<?php

namespace App\Models\Scopes;

use App\Support\BranchAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Limita cualquier consulta del modelo a los registros de la sucursal del usuario.
 * Al ser un scope global aplica también a la carga por ID de las rutas, relaciones,
 * whereHas, conteos y agregados.
 */
class BranchScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (BranchAccess::isUnrestricted()) {
            return;
        }

        $branchId = BranchAccess::branchId();

        if ($branchId === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $model->restrictToBranch($builder, $branchId);
    }
}
