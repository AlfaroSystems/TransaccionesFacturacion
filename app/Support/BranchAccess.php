<?php

namespace App\Support;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Determina qué datos puede ver el usuario actual según su sucursal.
 *
 * - Administrador: sin restricción (todas las sucursales y empresas).
 * - Cualquier otro usuario: solo su sucursal; sin sucursal asignada no ve nada.
 * - Sin usuario autenticado: sin restricción en consola (comandos, colas, seeders)
 *   y sin acceso en peticiones web.
 */
class BranchAccess
{
    public static function isUnrestricted(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return app()->runningInConsole();
        }

        return $user->isAdmin();
    }

    /**
     * Sucursal del usuario autenticado, o null si no tiene una asignada.
     */
    public static function branchId(): ?int
    {
        $branchId = Auth::user()?->id_branch;

        return $branchId !== null ? (int) $branchId : null;
    }

    /**
     * El usuario pertenece a la sucursal del departamento de compras (Casa Matriz), que
     * recibe las solicitudes de compra de las demás sucursales de su empresa.
     */
    public static function isPurchasingDepartment(): bool
    {
        return (bool) self::userBranch()?->is_purchasing_department;
    }

    /**
     * El usuario puede modificar los registros de esa sucursal: el administrador, o
     * cualquier usuario si es la suya. El departamento de compras ve las sucursales y
     * bodegas de toda su empresa, pero solo modifica las de su sucursal.
     */
    public static function ownsBranch(int|string|null $branchId): bool
    {
        return self::isUnrestricted() || ($branchId !== null && (int) $branchId === self::branchId());
    }

    /**
     * Subconsulta con los id de las sucursales de la empresa del usuario.
     */
    public static function companyBranchIds(): Builder
    {
        return Branch::queryAllBranches()
            ->where('id_company', self::companyId())
            ->select('id_branch');
    }

    /**
     * Empresa de la sucursal del usuario.
     */
    public static function companyId(): ?int
    {
        $companyId = self::userBranch()?->id_company;

        return $companyId !== null ? (int) $companyId : null;
    }

    private static function userBranch(): ?Branch
    {
        $user = Auth::user();

        if (! $user || $user->id_branch === null) {
            return null;
        }

        // Se carga una vez por petición; sin scope para no depender de BranchScope
        if (! $user->relationLoaded('branch')) {
            $user->setRelation('branch', Branch::queryAllBranches()->find($user->id_branch));
        }

        return $user->branch;
    }
}
