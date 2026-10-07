<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Grupos (módulos) en que la pantalla de roles muestra los permisos, según el prefijo
 * de su id: "purchase_orders.ver" va en "Órdenes de Compra".
 *
 * Un permiso nuevo debe tener su prefijo en algún grupo para poder asignarse desde la
 * pantalla (RolePermissionGroupsTest lo verifica con los permisos del seeder).
 */
class PermissionGroups
{
    /**
     * [título, color del punto, prefijos]. Los colores son clases de Tailwind:
     * app.css incluye este archivo en @source para que se generen.
     */
    public const GROUPS = [
        ['Gestión de Usuarios', 'bg-blue-500', ['usuarios.']],
        ['Configuración de Empresa y Sucursales', 'bg-indigo-500', ['companies.', 'branches.']],
        ['Gestión de Almacenes y Categorías', 'bg-teal-500', ['warehouses.', 'warehouse_categories.']],
        ['Ubicaciones y Mapa', 'bg-cyan-500', ['locations.']],
        ['Seguridad y Auditoría', 'bg-emerald-500', ['roles.', 'bitacora.']],
        ['Catálogo de Productos y Unidades', 'bg-rose-500', ['products.', 'categories.', 'subcategories.', 'units.']],
        ['Gestión de Proveedores', 'bg-purple-500', ['suppliers.']],
        ['Compras: Solicitudes de Compra', 'bg-amber-500', ['purchase_requests.']],
        ['Compras: Solicitudes de Cotización', 'bg-sky-500', ['purchase_quotation_requests.', 'purchase_quotations.']],
        ['Compras: Órdenes de Compra', 'bg-orange-500', ['purchase_orders.']],
        ['Compras: Facturas / Compras', 'bg-lime-500', ['purchases.']],
        ['Compras: Retaceos', 'bg-pink-500', ['retaceos.']],
        ['Compras: Tipos de Gastos', 'bg-slate-500', ['expense_types.']],
    ];

    /**
     * Permisos agrupados, en el orden de GROUPS; se omiten los grupos sin permisos.
     *
     * @return array<int, array{title: string, color: string, permissions: Collection}>
     */
    public static function group(Collection $permissions): array
    {
        $groups = [];

        foreach (self::GROUPS as [$title, $color, $prefixes]) {
            $groupPermissions = $permissions
                ->filter(fn ($permission) => self::matches($permission->id_permission, $prefixes))
                ->values();

            if ($groupPermissions->isNotEmpty()) {
                $groups[] = ['title' => $title, 'color' => $color, 'permissions' => $groupPermissions];
            }
        }

        return $groups;
    }

    /**
     * Permisos que no pertenecen a ningún grupo (no aparecen en la pantalla de roles).
     */
    public static function ungrouped(Collection $permissions): Collection
    {
        $prefixes = array_merge(...array_column(self::GROUPS, 2));

        return $permissions
            ->reject(fn ($permission) => self::matches($permission->id_permission, $prefixes))
            ->values();
    }

    private static function matches(string $permissionId, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($permissionId, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
