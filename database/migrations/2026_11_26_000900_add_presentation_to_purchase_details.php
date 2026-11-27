<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La línea de recepción de una compra puede indicar en qué presentación/empaque se recibe la
 * mercadería (Unidad, Caja...). Al crear el lote se convierte cantidad × factor a unidades base
 * del inventario (ver InventoryService::crearLotes), igual que ya hace la venta.
 *
 * Las filas existentes se asignan a la presentación por defecto "Unidad" (factor 1), de modo que
 * el histórico no cambia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_details', function (Blueprint $table) {
            $table->foreignId('id_product_presentation')->nullable()->after('id_unit')
                ->constrained('product_presentations', 'id_product_presentation')->nullOnDelete();
        });

        // Lo recibido hasta ahora se contó en unidades base (factor 1): apuntar a "Unidad"
        DB::statement("UPDATE purchase_details pd
            SET id_product_presentation = ppr.id_product_presentation
            FROM product_presentations ppr
            WHERE ppr.id_product = pd.id_product AND ppr.name = 'Unidad'");
    }

    public function down(): void
    {
        Schema::table('purchase_details', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_product_presentation');
        });
    }
};
