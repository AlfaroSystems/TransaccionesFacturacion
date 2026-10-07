<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Crear, editar y desactivar empresas y administrar roles pasan a ser solo del admin
 * (Gate 'admin'): se quitan sus permisos asignables. Un rol con roles.administrar podía
 * darse cualquier otro permiso. Sus asignaciones a roles se borran en cascada.
 */
return new class extends Migration
{
    private array $permissions = [
        ['roles.administrar', 'Administrar Roles y Permisos', 'Permite configurar roles y asignarles permisos.', 'manage'],
        ['companies.crear', 'Crear Empresas', 'Permite registrar nuevas empresas.', 'create'],
        ['companies.editar', 'Editar Empresas', 'Permite modificar la información de las empresas.', 'edit'],
        ['companies.eliminar', 'Eliminar Empresas', 'Permite eliminar empresas del sistema.', 'destroy'],
    ];

    public function up(): void
    {
        DB::table('permissions')->whereIn('id_permission', array_column($this->permissions, 0))->delete();
    }

    /**
     * Restaura los permisos (sin las asignaciones a roles que tenían).
     */
    public function down(): void
    {
        foreach ($this->permissions as [$id, $name, $description, $action]) {
            DB::table('permissions')->insertOrIgnore([
                'id_permission' => $id,
                'name' => $name,
                'description' => $description,
                'action' => $action,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
