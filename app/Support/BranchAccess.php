<?php

namespace App\Support;

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
}
