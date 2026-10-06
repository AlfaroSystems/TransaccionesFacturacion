<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ajusta los nombres de tablas y columnas al diagrama entidad-relación del sistema,
 * con los nombres tal como aparecen en él (incluidos "supliers" y "addres"), y agrega
 * lo que faltaba: is_costable en los gastos de orden, un ID propio en las tablas
 * intermedias de roles y permisos, y dos llaves foráneas de las solicitudes de cotización.
 */
return new class extends Migration
{
    /**
     * Tablas: nombre actual => nombre del diagrama.
     */
    private array $tables = [
        'audit_logs'           => 'logs',
        'role_user'            => 'users_roles',
        'permission_role'      => 'roles_permissions',
        'warehouse_categories' => 'warehouse_category',
        'suppliers'            => 'supliers',
        'supplier_contacts'    => 'supliers_contacts',
    ];

    /**
     * Columnas por tabla (con el nombre nuevo de la tabla): nombre actual => nombre del diagrama.
     */
    private array $columns = [
        'logs'              => ['id' => 'id_log', 'user_id' => 'id_user'],
        'users'             => ['id' => 'id_user', 'name' => 'username', 'password' => 'password_hash'],
        'users_roles'       => ['user_id' => 'id_user', 'role_id' => 'id_role'],
        'roles'             => ['id' => 'id_role'],
        'permissions'       => ['id' => 'id_permission'],
        'roles_permissions' => ['role_id' => 'id_role', 'permission_id' => 'id_permission'],
        'companies'         => [
            'id' => 'id_company', 'address' => 'addres', 'department_id' => 'id_department',
            'municipality_id' => 'id_municipality', 'district_id' => 'id_district',
        ],
        'branches'          => [
            'id' => 'id_branch', 'company_id' => 'id_company', 'address' => 'addres',
            'department_id' => 'id_department', 'municipality_id' => 'id_municipality', 'district_id' => 'id_district',
        ],
        'warehouses'         => ['id' => 'id_warehouse', 'branch_id' => 'id_branch', 'warehouse_category_id' => 'id_warehouse_category'],
        'locations'          => ['id' => 'id_location', 'warehouse_id' => 'id_warehouse', 'pasillo' => 'aisle'],
        'warehouse_category' => ['id' => 'id_warehouse_category'],
        'departments'        => ['id' => 'id_department'],
        'municipalities'     => ['id' => 'id_municipality', 'department_id' => 'id_department'],
        'districts'          => ['id' => 'id_district', 'municipality_id' => 'id_municipality'],
        'products'           => ['id' => 'id_product'],
        'sub_categories'     => ['id' => 'id_sub_category'],
        'categories'         => ['id' => 'id_category'],
        'units'              => ['id' => 'id_unit'],
        // No están en el diagrama; se renombran por consistencia con el resto
        'supliers'           => ['department_id' => 'id_department', 'municipality_id' => 'id_municipality', 'district_id' => 'id_district'],
        'supliers_contacts'  => ['id_contact' => 'id_suplier_contact'],
    ];

    public function up(): void
    {
        // 1. Tablas
        foreach ($this->tables as $from => $to) {
            Schema::rename($from, $to);
        }

        // 2. Columnas
        foreach ($this->columns as $table => $renames) {
            Schema::table($table, function (Blueprint $blueprint) use ($renames) {
                foreach ($renames as $from => $to) {
                    $blueprint->renameColumn($from, $to);
                }
            });
        }

        // 3. users.status ('active'/'inactive') => users.is_active (booleano)
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
        });
        DB::table('users')->update(['is_active' => DB::raw("status = 'active'")]);
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        // 4. ID propio en las tablas intermedias (antes llave compuesta)
        DB::statement('ALTER TABLE users_roles DROP CONSTRAINT role_user_pkey');
        Schema::table('users_roles', function (Blueprint $table) {
            $table->id('id_user_role');
            $table->unique(['id_user', 'id_role']);
        });

        DB::statement('ALTER TABLE roles_permissions DROP CONSTRAINT permission_role_pkey');
        Schema::table('roles_permissions', function (Blueprint $table) {
            $table->id('id_role_permission');
            $table->unique(['id_role', 'id_permission']);
        });

        // 5. Gastos de orden que forman parte del costo (retaceo)
        Schema::table('purchase_order_expenses', function (Blueprint $table) {
            $table->boolean('is_costable')->default(true);
        });

        // 6. Relaciones de las solicitudes de cotización que no tenían llave foránea
        Schema::table('purchase_quotation_requests', function (Blueprint $table) {
            $table->foreign('id_purchase_quotation')
                ->references('id_purchase_quotation')->on('purchase_quotations')
                ->restrictOnDelete();
        });
        Schema::table('purchase_quotation_request_details', function (Blueprint $table) {
            $table->foreign('id_purchase_quotation_detail')
                ->references('id_purchase_quotation_detail')->on('purchase_quotation_details')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_quotation_request_details', function (Blueprint $table) {
            $table->dropForeign(['id_purchase_quotation_detail']);
        });
        Schema::table('purchase_quotation_requests', function (Blueprint $table) {
            $table->dropForeign(['id_purchase_quotation']);
        });

        Schema::table('purchase_order_expenses', function (Blueprint $table) {
            $table->dropColumn('is_costable');
        });

        Schema::table('roles_permissions', function (Blueprint $table) {
            $table->dropUnique(['id_role', 'id_permission']);
            $table->dropColumn('id_role_permission');
        });
        DB::statement('ALTER TABLE roles_permissions ADD CONSTRAINT permission_role_pkey PRIMARY KEY (id_role, id_permission)');

        Schema::table('users_roles', function (Blueprint $table) {
            $table->dropUnique(['id_user', 'id_role']);
            $table->dropColumn('id_user_role');
        });
        DB::statement('ALTER TABLE users_roles ADD CONSTRAINT role_user_pkey PRIMARY KEY (id_user, id_role)');

        Schema::table('users', function (Blueprint $table) {
            $table->string('status')->default('active');
        });
        DB::table('users')->update(['status' => DB::raw("CASE WHEN is_active THEN 'active' ELSE 'inactive' END")]);
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        foreach (array_reverse($this->columns, true) as $table => $renames) {
            Schema::table($table, function (Blueprint $blueprint) use ($renames) {
                foreach ($renames as $from => $to) {
                    $blueprint->renameColumn($to, $from);
                }
            });
        }

        foreach (array_reverse($this->tables, true) as $from => $to) {
            Schema::rename($to, $from);
        }
    }
};
