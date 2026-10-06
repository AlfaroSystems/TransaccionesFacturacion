<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Al renombrar tablas y columnas (rename_schema_to_match_diagram), PostgreSQL conservó
 * los nombres anteriores de llaves, índices y secuencias (por ejemplo,
 * "audit_logs_user_id_foreign" en la tabla logs). Esta migración los renombra con la
 * convención de Laravel para la tabla y columna actuales, de modo que migraciones futuras
 * puedan referirse a ellos con dropForeign(['id_user']), dropIndex, etc.
 *
 * La lista es explícita para que down() restaure exactamente los nombres anteriores.
 */
return new class extends Migration
{
    /**
     * [tipo, tabla, nombre anterior, nombre nuevo]
     */
    private array $renames = [
        ['FK',     'branches',           'branches_company_id_foreign',               'branches_id_company_foreign'],
        ['FK',     'branches',           'branches_department_id_foreign',            'branches_id_department_foreign'],
        ['FK',     'branches',           'branches_district_id_foreign',              'branches_id_district_foreign'],
        ['FK',     'branches',           'branches_municipality_id_foreign',          'branches_id_municipality_foreign'],
        ['FK',     'companies',          'companies_department_id_foreign',           'companies_id_department_foreign'],
        ['FK',     'companies',          'companies_district_id_foreign',             'companies_id_district_foreign'],
        ['FK',     'companies',          'companies_municipality_id_foreign',         'companies_id_municipality_foreign'],
        ['FK',     'districts',          'districts_municipality_id_foreign',         'districts_id_municipality_foreign'],
        ['FK',     'locations',          'locations_warehouse_id_foreign',            'locations_id_warehouse_foreign'],
        ['FK',     'logs',               'audit_logs_user_id_foreign',                'logs_id_user_foreign'],
        ['FK',     'municipalities',     'municipalities_department_id_foreign',      'municipalities_id_department_foreign'],
        ['FK',     'roles_permissions',  'permission_role_permission_id_foreign',     'roles_permissions_id_permission_foreign'],
        ['FK',     'roles_permissions',  'permission_role_role_id_foreign',           'roles_permissions_id_role_foreign'],
        ['FK',     'supliers',           'suppliers_department_id_foreign',           'supliers_id_department_foreign'],
        ['FK',     'supliers',           'suppliers_district_id_foreign',             'supliers_id_district_foreign'],
        ['FK',     'supliers',           'suppliers_municipality_id_foreign',         'supliers_id_municipality_foreign'],
        ['FK',     'supliers_contacts',  'supplier_contacts_id_supplier_foreign',     'supliers_contacts_id_supplier_foreign'],
        ['FK',     'users_roles',        'role_user_role_id_foreign',                 'users_roles_id_role_foreign'],
        ['FK',     'users_roles',        'role_user_user_id_foreign',                 'users_roles_id_user_foreign'],
        ['FK',     'warehouses',         'warehouses_branch_id_foreign',              'warehouses_id_branch_foreign'],
        ['FK',     'warehouses',         'warehouses_warehouse_category_id_foreign',  'warehouses_id_warehouse_category_foreign'],
        ['INDEX',  'logs',               'audit_logs_auditable_type_id_record_index', 'logs_auditable_type_id_record_index'],
        ['PK',     'logs',               'audit_logs_pkey',                           'logs_pkey'],
        ['PK',     'supliers',           'suppliers_pkey',                            'supliers_pkey'],
        ['PK',     'supliers_contacts',  'supplier_contacts_pkey',                    'supliers_contacts_pkey'],
        ['PK',     'warehouse_category', 'warehouse_categories_pkey',                 'warehouse_category_pkey'],
        ['UNIQUE', 'supliers',           'suppliers_email_unique',                    'supliers_email_unique'],
        ['SEQ',    'branches',           'branches_id_seq',                           'branches_id_branch_seq'],
        ['SEQ',    'categories',         'categories_id_seq',                         'categories_id_category_seq'],
        ['SEQ',    'companies',          'companies_id_seq',                          'companies_id_company_seq'],
        ['SEQ',    'departments',        'departments_id_seq',                        'departments_id_department_seq'],
        ['SEQ',    'districts',          'districts_id_seq',                          'districts_id_district_seq'],
        ['SEQ',    'locations',          'locations_id_seq',                          'locations_id_location_seq'],
        ['SEQ',    'logs',               'audit_logs_id_seq',                         'logs_id_log_seq'],
        ['SEQ',    'municipalities',     'municipalities_id_seq',                     'municipalities_id_municipality_seq'],
        ['SEQ',    'products',           'products_id_seq',                           'products_id_product_seq'],
        ['SEQ',    'roles',              'roles_id_seq',                              'roles_id_role_seq'],
        ['SEQ',    'sub_categories',     'sub_categories_id_seq',                     'sub_categories_id_sub_category_seq'],
        ['SEQ',    'supliers',           'suppliers_id_supplier_seq',                 'supliers_id_supplier_seq'],
        ['SEQ',    'supliers_contacts',  'supplier_contacts_id_contact_seq',          'supliers_contacts_id_suplier_contact_seq'],
        ['SEQ',    'units',              'units_id_seq',                              'units_id_unit_seq'],
        ['SEQ',    'users',              'users_id_seq',                              'users_id_user_seq'],
        ['SEQ',    'warehouse_category', 'warehouse_categories_id_seq',               'warehouse_category_id_warehouse_category_seq'],
        ['SEQ',    'warehouses',         'warehouses_id_seq',                         'warehouses_id_warehouse_seq'],
    ];

    public function up(): void
    {
        foreach ($this->renames as [$type, $table, $from, $to]) {
            $this->rename($type, $table, $from, $to);
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->renames) as [$type, $table, $from, $to]) {
            $this->rename($type, $table, $to, $from);
        }
    }

    private function rename(string $type, string $table, string $from, string $to): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $sql = match ($type) {
            // Al renombrar una restricción, PostgreSQL renombra también su índice
            'FK', 'PK', 'UNIQUE' => "ALTER TABLE \"{$table}\" RENAME CONSTRAINT \"{$from}\" TO \"{$to}\"",
            'INDEX'              => "ALTER INDEX \"{$from}\" RENAME TO \"{$to}\"",
            'SEQ'                => "ALTER SEQUENCE \"{$from}\" RENAME TO \"{$to}\"",
        };

        DB::statement($sql);
    }
};
