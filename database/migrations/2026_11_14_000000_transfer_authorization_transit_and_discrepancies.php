<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Flujo completo de traslados:
 *
 * Borrador → Por autorizar → Autorizado (o rechazado: vuelve a borrador con su motivo) →
 * En preparación → En tránsito (la mercadería está en el espacio de carga del vehículo) →
 * Recepción parcial (lo que falta sigue en el vehículo) → Con discrepancia → Recibido.
 *
 * - inventory_transfers: quién y cuándo autorizó, el motivo del rechazo, cuándo empezó la
 *   preparación y el espacio del vehículo donde viaja la mercadería.
 * - inventory_transfer_discrepancies: faltantes, sobrantes y daños de cada línea, y cuándo se
 *   regularizaron.
 * - Los traslados despachados pasan a "en tránsito".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_transfers', function (Blueprint $table) {
            $table->timestamp('authorized_at')->nullable();
            $table->foreignId('id_authorized_by')->nullable()->constrained('users', 'id_user')->nullOnDelete();
            $table->text('status_reason')->nullable();
            $table->timestamp('prepared_at')->nullable();
            $table->foreignId('id_vehicle_location')->nullable()->constrained('vehicle_locations', 'id_vehicle_location')->restrictOnDelete();
        });

        // Los estados nuevos son más largos (pending_authorization)
        DB::statement('ALTER TABLE inventory_transfers ALTER COLUMN status TYPE varchar(30)');
        DB::table('inventory_transfers')->where('status', 'dispatched')->update(['status' => 'in_transit']);

        Schema::create('inventory_transfer_discrepancies', function (Blueprint $table) {
            $table->id('id_inventory_transfer_discrepancy');
            $table->foreignId('id_inventory_transfer_detail')->constrained('inventory_transfer_details', 'id_inventory_transfer_detail')->cascadeOnDelete();
            $table->string('discrepancy_type', 20);
            $table->decimal('quantity', 12, 4);
            $table->foreignId('id_destination_inventory_location')->nullable()->constrained('inventory_locations', 'id_inventory_location')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('id_user')->nullable()->constrained('users', 'id_user')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('regularized_at')->nullable();

            $table->index('id_inventory_transfer_detail');
        });

        DB::statement("ALTER TABLE inventory_transfer_discrepancies ADD CONSTRAINT inventory_transfer_discrepancies_type_check CHECK (discrepancy_type IN ('shortage', 'surplus', 'damage'))");
        DB::statement('ALTER TABLE inventory_transfer_discrepancies ADD CONSTRAINT inventory_transfer_discrepancies_quantity_check CHECK (quantity > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transfer_discrepancies');

        DB::table('inventory_transfers')->whereIn('status', ['in_transit', 'partially_received', 'with_discrepancy'])->update(['status' => 'dispatched']);
        DB::table('inventory_transfers')->whereIn('status', ['pending_authorization', 'authorized', 'preparing'])->update(['status' => 'draft']);
        DB::statement('ALTER TABLE inventory_transfers ALTER COLUMN status TYPE varchar(20)');

        Schema::table('inventory_transfers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_vehicle_location');
            $table->dropConstrainedForeignId('id_authorized_by');
            $table->dropColumn(['authorized_at', 'status_reason', 'prepared_at']);
        });
    }
};
