<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Crear Permisos
        $permissions = [
            // Usuarios
            [
                'id' => 'usuarios.ver',
                'name' => 'Ver Usuarios',
                'description' => 'Permite listar y ver la información de los usuarios.',
                'action' => 'index'
            ],
            [
                'id' => 'usuarios.crear',
                'name' => 'Crear Usuarios',
                'description' => 'Permite crear nuevos usuarios en el sistema.',
                'action' => 'create'
            ],
            [
                'id' => 'usuarios.editar',
                'name' => 'Editar Usuarios',
                'description' => 'Permite modificar la información de los usuarios existentes.',
                'action' => 'edit'
            ],
            [
                'id' => 'usuarios.eliminar',
                'name' => 'Eliminar Usuarios',
                'description' => 'Permite eliminar usuarios del sistema.',
                'action' => 'destroy'
            ],
            // Roles y Bitácora
            [
                'id' => 'roles.administrar',
                'name' => 'Administrar Roles y Permisos',
                'description' => 'Permite configurar roles y asignarles permisos.',
                'action' => 'manage'
            ],
            [
                'id' => 'bitacora.ver',
                'name' => 'Ver Bitácora de Logs',
                'description' => 'Permite revisar la bitácora de auditoría de actividad del sistema.',
                'action' => 'logs'
            ],
            // Empleados
            [
                'id' => 'empleados.ver',
                'name' => 'Ver Empleados',
                'description' => 'Permite ver el listado y detalle de los empleados.',
                'action' => 'index'
            ],
            [
                'id' => 'empleados.crear',
                'name' => 'Crear Empleados',
                'description' => 'Permite registrar nuevos empleados.',
                'action' => 'create'
            ],
            [
                'id' => 'empleados.editar',
                'name' => 'Editar Empleados',
                'description' => 'Permite modificar la información de los empleados.',
                'action' => 'edit'
            ],
            [
                'id' => 'empleados.eliminar',
                'name' => 'Eliminar Empleados',
                'description' => 'Permite eliminar empleados del sistema.',
                'action' => 'destroy'
            ],
            // Sucursales
            [
                'id' => 'branches.ver',
                'name' => 'Ver Sucursales',
                'description' => 'Permite ver el listado y detalle de las sucursales.',
                'action' => 'index'
            ],
            [
                'id' => 'branches.crear',
                'name' => 'Crear Sucursales',
                'description' => 'Permite registrar nuevas sucursales.',
                'action' => 'create'
            ],
            [
                'id' => 'branches.editar',
                'name' => 'Editar Sucursales',
                'description' => 'Permite modificar la información de las sucursales.',
                'action' => 'edit'
            ],
            [
                'id' => 'branches.eliminar',
                'name' => 'Eliminar Sucursales',
                'description' => 'Permite eliminar sucursales del sistema.',
                'action' => 'destroy'
            ],
            // Bodegas
            [
                'id' => 'warehouses.ver',
                'name' => 'Ver Bodegas',
                'description' => 'Permite ver el listado y detalle de las bodegas.',
                'action' => 'index'
            ],
            [
                'id' => 'warehouses.crear',
                'name' => 'Crear Bodegas',
                'description' => 'Permite registrar nuevas bodegas.',
                'action' => 'create'
            ],
            [
                'id' => 'warehouses.editar',
                'name' => 'Editar Bodegas',
                'description' => 'Permite modificar la información de las bodegas.',
                'action' => 'edit'
            ],
            [
                'id' => 'warehouses.eliminar',
                'name' => 'Eliminar Bodegas',
                'description' => 'Permite eliminar bodegas del sistema.',
                'action' => 'destroy'
            ],
            // Categorías de Bodega
            [
                'id' => 'warehouse_categories.ver',
                'name' => 'Ver Categorías de Bodegas',
                'description' => 'Permite ver el listado de categorías de bodegas.',
                'action' => 'index'
            ],
            [
                'id' => 'warehouse_categories.crear',
                'name' => 'Crear Categorías de Bodegas',
                'description' => 'Permite registrar nuevas categorías de bodegas.',
                'action' => 'create'
            ],
            [
                'id' => 'warehouse_categories.editar',
                'name' => 'Editar Categorías de Bodegas',
                'description' => 'Permite modificar las categorías de bodegas.',
                'action' => 'edit'
            ],
            [
                'id' => 'warehouse_categories.eliminar',
                'name' => 'Eliminar Categorías de Bodegas',
                'description' => 'Permite eliminar categorías de bodegas.',
                'action' => 'destroy'
            ],
            // Ubicaciones
            [
                'id' => 'locations.ver',
                'name' => 'Ver Ubicaciones y Mapa',
                'description' => 'Permite ver el listado y mapa de ubicaciones físicas de bodega.',
                'action' => 'index'
            ],
            [
                'id' => 'locations.crear',
                'name' => 'Crear Ubicaciones',
                'description' => 'Permite crear nuevas ubicaciones físicas.',
                'action' => 'create'
            ],
            [
                'id' => 'locations.editar',
                'name' => 'Editar Ubicaciones',
                'description' => 'Permite modificar ubicaciones físicas existentes.',
                'action' => 'edit'
            ],
            [
                'id' => 'locations.eliminar',
                'name' => 'Eliminar Ubicaciones',
                'description' => 'Permite eliminar ubicaciones físicas.',
                'action' => 'destroy'
            ],
            // Empresas
            [
                'id' => 'companies.ver',
                'name' => 'Ver Empresas',
                'description' => 'Permite ver el listado y detalle de las empresas.',
                'action' => 'index'
            ],
            [
                'id' => 'companies.crear',
                'name' => 'Crear Empresas',
                'description' => 'Permite registrar nuevas empresas.',
                'action' => 'create'
            ],
            [
                'id' => 'companies.editar',
                'name' => 'Editar Empresas',
                'description' => 'Permite modificar la información de las empresas.',
                'action' => 'edit'
            ],
            [
                'id' => 'companies.eliminar',
                'name' => 'Eliminar Empresas',
                'description' => 'Permite eliminar empresas del sistema.',
                'action' => 'destroy'
            ],
            // Unidades de Medida
            [
                'id' => 'units.ver',
                'name' => 'Ver Unidades de Medida',
                'description' => 'Permite ver el listado de unidades de medida.',
                'action' => 'index'
            ],
            [
                'id' => 'units.crear',
                'name' => 'Crear Unidades de Medida',
                'description' => 'Permite registrar nuevas unidades de medida.',
                'action' => 'create'
            ],
            [
                'id' => 'units.editar',
                'name' => 'Editar Unidades de Medida',
                'description' => 'Permite modificar unidades de medida existentes.',
                'action' => 'edit'
            ],
            [
                'id' => 'units.desactivar',
                'name' => 'Activar/Desactivar Unidades',
                'description' => 'Permite cambiar el estado (activo/inactivo) de una unidad de medida.',
                'action' => 'toggle'
            ]
        ];

        foreach ($permissions as $permissionData) {
            Permission::updateOrCreate(['id' => $permissionData['id']], $permissionData);
        }

        // 2. Crear Roles
        $adminRole = Role::updateOrCreate(
            ['name' => 'admin'],
            ['description' => 'Administrador General del Sistema con acceso total.']
        );

        // 3. Asignar Permisos a Roles
        // El administrador obtiene todos los permisos
        $allPermissionIds = Permission::pluck('id')->toArray();
        $adminRole->permissions()->sync($allPermissionIds);

        // 4. Asignar Rol de Administrador al usuario principal y de Jon
        $adminUsers = User::whereIn('email', ['jon.virgi@gmail.com'])->get();
        foreach ($adminUsers as $user) {
            $user->roles()->sync([$adminRole->id]);
        }
    }
}
