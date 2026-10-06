<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Los documentos de compra (solicitudes, cotizaciones, órdenes, compras y retaceos) no
 * deben desaparecer ni quedar sin sucursal o bodega porque se borre un dato maestro u
 * otro documento: esas llaves pasan a RESTRICT. Las líneas de detalle de cada documento
 * se siguen borrando junto con él (CASCADE), que es lo esperado.
 */
return new class extends Migration
{
    /**
     * [tabla, columna, tabla referenciada, columna referenciada, regla original]
     */
    private array $foreignKeys = [
        ['branches', 'company_id', 'companies', 'id', 'cascade'],
        ['purchase_quotation_requests', 'id_purchase_request', 'purchase_requests', 'id_purchase_request', 'cascade'],
        ['purchase_quotations', 'id_purchase_quotation_request', 'purchase_quotation_requests', 'id_purchase_quotation_request', 'cascade'],
        ['purchase_quotations', 'id_supplier', 'suppliers', 'id_supplier', 'cascade'],
        ['supplier_quotations', 'purchase_quotation_request_id', 'purchase_quotation_requests', 'id_purchase_quotation_request', 'cascade'],
        ['supplier_quotations', 'supplier_id', 'suppliers', 'id_supplier', 'cascade'],
        ['purchase_orders', 'id_supplier', 'suppliers', 'id_supplier', 'cascade'],
        ['purchase_orders', 'id_branch', 'branches', 'id', 'set null'],
        ['purchase_orders', 'id_warehouse', 'warehouses', 'id', 'set null'],
        ['purchase_orders', 'id_purchase_quotation', 'purchase_quotations', 'id_purchase_quotation', 'set null'],
        ['purchases', 'id_purchase_order', 'purchase_orders', 'id_purchase_order', 'cascade'],
        ['purchases', 'id_supplier', 'suppliers', 'id_supplier', 'cascade'],
        ['purchases', 'id_branch', 'branches', 'id', 'set null'],
        ['purchases', 'id_warehouse', 'warehouses', 'id', 'set null'],
        ['retaceos', 'id_purchase', 'purchases', 'id_purchase', 'cascade'],
        ['retaceos', 'id_supplier', 'suppliers', 'id_supplier', 'cascade'],
    ];

    public function up(): void
    {
        $this->redefine(fn (array $fk) => 'restrict');
    }

    public function down(): void
    {
        $this->redefine(fn (array $fk) => $fk[4]);
    }

    private function redefine(callable $onDelete): void
    {
        foreach ($this->foreignKeys as $fk) {
            [$table, $column, $references, $referencedColumn] = $fk;

            Schema::table($table, function (Blueprint $blueprint) use ($column, $references, $referencedColumn, $onDelete, $fk) {
                $blueprint->dropForeign([$column]);

                $blueprint->foreign($column)
                    ->references($referencedColumn)
                    ->on($references)
                    ->onDelete($onDelete($fk));
            });
        }
    }
};
