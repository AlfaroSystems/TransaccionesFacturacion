<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Alinea purchase_orders, purchase_order_details y purchase_order_expenses con el esquema
 * de sus migraciones de creación.
 *
 * Las bases que ejecutaron la primera versión de esas migraciones (antes de que se
 * reescribieran) recibieron las columnas faltantes mediante fix_purchase_orders_schema,
 * como opcionales, con borrado en cascada desde productos y tipos de gasto, otra
 * precisión numérica y otros nombres de secuencia. En una base creada desde cero el
 * esquema ya es el correcto y esta migración no cambia nada.
 */
return new class extends Migration
{
    /**
     * Columnas obligatorias en el esquema de creación.
     */
    private array $requiredColumns = [
        'purchase_orders'         => ['uuid', 'purchase_order_code', 'id_supplier', 'order_date'],
        'purchase_order_details'  => ['id_purchase_order', 'id_product'],
        'purchase_order_expenses' => ['id_purchase_order', 'id_expense_type'],
    ];

    /**
     * Secuencias de ID con el nombre que tenían antes de renombrar la columna "id".
     */
    private array $sequences = [
        'purchase_orders_id_seq'         => 'purchase_orders_id_purchase_order_seq',
        'purchase_order_details_id_seq'  => 'purchase_order_details_id_purchase_order_detail_seq',
        'purchase_order_expenses_id_seq' => 'purchase_order_expenses_id_purchase_order_expense_seq',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // 1. Columnas obligatorias (SET NOT NULL no hace nada si ya lo son)
        foreach ($this->requiredColumns as $table => $columns) {
            foreach ($columns as $column) {
                DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} SET NOT NULL");
            }
        }

        // 2. Precisión y default de las líneas de la orden
        DB::statement('ALTER TABLE purchase_order_details ALTER COLUMN quantity TYPE numeric(12, 4)');
        DB::statement('ALTER TABLE purchase_order_details ALTER COLUMN quantity DROP DEFAULT');
        DB::statement('ALTER TABLE purchase_order_details ALTER COLUMN tax_rate TYPE numeric(5, 2)');

        // 3. Un producto o tipo de gasto en uso no se puede borrar (antes borraba las líneas y gastos)
        Schema::table('purchase_order_details', function (Blueprint $table) {
            $table->dropForeign(['id_product']);
            $table->foreign('id_product')->references('id')->on('products')->restrictOnDelete();
        });

        Schema::table('purchase_order_expenses', function (Blueprint $table) {
            $table->dropForeign(['id_expense_type']);
            $table->foreign('id_expense_type')->references('id_expense_type')->on('expense_types')->restrictOnDelete();
        });

        // 4. Nombres de las secuencias de ID, iguales a los de una base nueva
        foreach ($this->sequences as $old => $new) {
            DB::statement("ALTER SEQUENCE IF EXISTS {$old} RENAME TO {$new}");
        }
    }

    /**
     * No se revierte: volver atrás reintroduciría la inconsistencia con las migraciones
     * de creación (columnas opcionales y borrado en cascada de líneas de órdenes).
     */
    public function down(): void
    {
        //
    }
};
