<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Los formularios de compra prellenan la ubicación de cada línea con la última donde entró el
 * producto en la bodega (PurchaseController::datosUbicaciones, con DISTINCT ON). Sin este
 * índice esa consulta recorre todos los movimientos en cada visita; con él lee solo la última
 * entrada de cada producto y bodega.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("CREATE INDEX inventory_movements_last_receipt_location ON inventory_movements (id_warehouse, id_product, id_inventory_movement DESC) WHERE movement_type = 'purchase_receipt' AND id_location IS NOT NULL");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS inventory_movements_last_receipt_location');
    }
};
