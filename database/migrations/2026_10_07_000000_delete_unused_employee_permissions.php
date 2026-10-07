<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Elimina los permisos empleados.*: no existe un módulo de empleados que los use ni están
 * en RoleAndPermissionSeeder. Sus asignaciones a roles se borran en cascada.
 */
return new class extends Migration
{
    private array $permissions = [
        ['empleados.ver', 'Ver Empleados', 'Permite ver el listado y detalle de los empleados.', 'index'],
        ['empleados.crear', 'Crear Empleados', 'Permite registrar nuevos empleados.', 'create'],
        ['empleados.editar', 'Editar Empleados', 'Permite modificar la información de los empleados.', 'edit'],
        ['empleados.eliminar', 'Eliminar Empleados', 'Permite eliminar empleados del sistema.', 'destroy'],
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
