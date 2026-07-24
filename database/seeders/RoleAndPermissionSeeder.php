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

        $editorRole = Role::updateOrCreate(
            ['name' => 'editor'],
            ['description' => 'Editor de contenido y gestión básica de usuarios.']
        );

        $userRole = Role::updateOrCreate(
            ['name' => 'user'],
            ['description' => 'Usuario regular / Lector con permisos básicos.']
        );

        // 3. Asignar Permisos a Roles
        // El administrador obtiene todos los permisos
        $allPermissionIds = Permission::pluck('id')->toArray();
        $adminRole->permissions()->sync($allPermissionIds);

        // El editor obtiene solo permisos de ver, crear y editar usuarios
        $editorPermissionIds = ['usuarios.ver', 'usuarios.crear', 'usuarios.editar'];
        $editorRole->permissions()->sync($editorPermissionIds);

        // 4. Asignar Rol de Administrador al usuario principal
        $adminUser = User::where('email', 'admin@facturacion.com')->first();
        if ($adminUser) {
            $adminUser->roles()->sync([$adminRole->id]);
        }
    }
}
