<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Inventario (fase 3), según el diagrama de la base de datos:
 *
 * - employees: personal de cada sucursal (motoristas, responsables de vehículos).
 * - vehicles: vehículos de la empresa, con su empleado responsable.
 * - vehicle_locations: espacios de carga de un vehículo, asignados a una bodega.
 * - inventory_locations: cada lugar donde puede estar un lote. Es una ubicación de bodega
 *   (location_type = location) o el espacio de un vehículo (vehicle); cada una tiene la suya.
 * - inventory_location_details: existencia actual de cada lote en cada ubicación de inventario.
 *   La mantiene cada movimiento; el kardex sigue siendo inventory_movements.
 * - inventory_movements e inventory_transfer_details apuntan a inventory_locations en lugar de
 *   locations; las líneas del traslado guardan además lo pedido (quantity_requested).
 * - inventory_transfers lleva el vehículo y el motorista en lugar de una ubicación de vehículo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id('id_employee');
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('dui', 10)->nullable()->unique();
            $table->string('position', 100)->nullable();
            $table->foreignId('id_branch')->constrained('branches', 'id_branch')->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->index('id_branch');
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id('id_vehicle');
            $table->string('brand', 100);
            $table->string('model', 100);
            $table->integer('year')->nullable();
            $table->string('license_plate', 20)->unique();
            $table->foreignId('id_responsible_employee')->nullable()->constrained('employees', 'id_employee')->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });

        // Un vehículo se elimina con sus espacios (si ninguno tiene historial)
        Schema::create('vehicle_locations', function (Blueprint $table) {
            $table->id('id_vehicle_location');
            $table->foreignId('id_vehicle')->constrained('vehicles', 'id_vehicle')->cascadeOnDelete();
            $table->foreignId('id_warehouse')->constrained('warehouses', 'id_warehouse')->restrictOnDelete();
            $table->string('code', 50);
            $table->integer('capacity')->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['id_vehicle', 'code']);
            $table->index('id_warehouse');
        });

        // Se borra con su ubicación o su espacio de vehículo; si ya tiene movimientos o
        // existencia, las llaves de esas tablas impiden borrarla
        Schema::create('inventory_locations', function (Blueprint $table) {
            $table->id('id_inventory_location');
            $table->foreignId('id_location')->nullable()->unique()->constrained('locations', 'id_location')->cascadeOnDelete();
            $table->foreignId('id_vehicle_location')->nullable()->unique()->constrained('vehicle_locations', 'id_vehicle_location')->cascadeOnDelete();
            $table->string('location_type', 20);
            $table->boolean('is_active')->default(true);
        });

        // Exactamente uno de los dos lugares, el que dice su tipo
        DB::statement("ALTER TABLE inventory_locations ADD CONSTRAINT inventory_locations_one_place CHECK (
            (location_type = 'location' AND id_location IS NOT NULL AND id_vehicle_location IS NULL)
            OR (location_type = 'vehicle' AND id_vehicle_location IS NOT NULL AND id_location IS NULL)
        )");

        Schema::create('inventory_location_details', function (Blueprint $table) {
            $table->id('id_inventory_location_detail');
            $table->foreignId('id_inventory_lot')->constrained('inventory_lots', 'id_inventory_lot')->restrictOnDelete();
            $table->foreignId('id_inventory_location')->constrained('inventory_locations', 'id_inventory_location')->restrictOnDelete();
            $table->decimal('quantity', 12, 4)->default(0);
            $table->timestamp('updated_at')->useCurrent();

            $table->unique(['id_inventory_lot', 'id_inventory_location']);
            $table->index('id_inventory_location');
        });

        // Nunca sale más de lo que hay
        DB::statement('ALTER TABLE inventory_location_details ADD CONSTRAINT inventory_location_details_quantity_not_negative CHECK (quantity >= 0)');

        // Cada ubicación de bodega existente tiene su ubicación de inventario
        DB::statement("INSERT INTO inventory_locations (id_location, location_type, is_active)
            SELECT id_location, 'location', is_active FROM locations ORDER BY id_location");

        // Movimientos: de la ubicación de bodega a su ubicación de inventario
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->foreignId('id_inventory_location')->nullable()->constrained('inventory_locations', 'id_inventory_location')->restrictOnDelete();
        });

        DB::statement('UPDATE inventory_movements m SET id_inventory_location = il.id_inventory_location
            FROM inventory_locations il WHERE il.id_location = m.id_location');

        DB::statement('DROP INDEX IF EXISTS inventory_movements_last_receipt_location');

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_location');
            $table->index('id_inventory_location');
        });

        // Para sugerir la última ubicación donde entró el producto en la bodega
        DB::statement("CREATE INDEX inventory_movements_last_receipt_location ON inventory_movements (id_warehouse, id_product, id_inventory_movement DESC) WHERE movement_type = 'purchase_receipt' AND id_inventory_location IS NOT NULL");

        // Existencia actual de cada lote en cada ubicación: la suma de sus movimientos
        DB::statement('INSERT INTO inventory_location_details (id_inventory_lot, id_inventory_location, quantity, updated_at)
            SELECT id_inventory_lot, id_inventory_location, SUM(quantity_in - quantity_out), MAX(created_at)
            FROM inventory_movements
            WHERE id_inventory_lot IS NOT NULL AND id_inventory_location IS NOT NULL
            GROUP BY id_inventory_lot, id_inventory_location
            HAVING SUM(quantity_in - quantity_out) <> 0');

        // Traslados: vehículo y motorista. La ubicación de vehículo era solo informativa
        Schema::table('inventory_transfers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_vehicle_location');
        });

        Schema::table('inventory_transfers', function (Blueprint $table) {
            $table->foreignId('id_vehicle')->nullable()->constrained('vehicles', 'id_vehicle')->restrictOnDelete();
            $table->foreignId('id_driver_employee')->nullable()->constrained('employees', 'id_employee')->restrictOnDelete();
        });

        // Líneas del traslado: ubicaciones de inventario y cantidad pedida
        Schema::table('inventory_transfer_details', function (Blueprint $table) {
            $table->foreignId('id_source_inventory_location')->nullable()->constrained('inventory_locations', 'id_inventory_location')->restrictOnDelete();
            $table->foreignId('id_destination_inventory_location')->nullable()->constrained('inventory_locations', 'id_inventory_location')->restrictOnDelete();
            $table->decimal('quantity_requested', 12, 4)->default(0);
        });

        DB::statement('UPDATE inventory_transfer_details d SET id_source_inventory_location = il.id_inventory_location
            FROM inventory_locations il WHERE il.id_location = d.id_source_location');
        DB::statement('UPDATE inventory_transfer_details d SET id_destination_inventory_location = il.id_inventory_location
            FROM inventory_locations il WHERE il.id_location = d.id_destination_location');
        DB::statement('UPDATE inventory_transfer_details SET quantity_requested = quantity_dispatched');
        // Lo despachado se registra al despachar; en borrador es cero
        DB::statement('ALTER TABLE inventory_transfer_details ALTER COLUMN quantity_dispatched SET DEFAULT 0');

        Schema::table('inventory_transfer_details', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_source_location');
            $table->dropConstrainedForeignId('id_destination_location');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transfer_details', function (Blueprint $table) {
            $table->foreignId('id_source_location')->nullable()->constrained('locations', 'id_location')->restrictOnDelete();
            $table->foreignId('id_destination_location')->nullable()->constrained('locations', 'id_location')->restrictOnDelete();
        });

        DB::statement('UPDATE inventory_transfer_details d SET id_source_location = il.id_location
            FROM inventory_locations il WHERE il.id_inventory_location = d.id_source_inventory_location');
        DB::statement('UPDATE inventory_transfer_details d SET id_destination_location = il.id_location
            FROM inventory_locations il WHERE il.id_inventory_location = d.id_destination_inventory_location');
        DB::statement('ALTER TABLE inventory_transfer_details ALTER COLUMN quantity_dispatched DROP DEFAULT');

        Schema::table('inventory_transfer_details', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_source_inventory_location');
            $table->dropConstrainedForeignId('id_destination_inventory_location');
            $table->dropColumn('quantity_requested');
        });

        Schema::table('inventory_transfers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_vehicle');
            $table->dropConstrainedForeignId('id_driver_employee');
        });

        Schema::table('inventory_transfers', function (Blueprint $table) {
            $table->foreignId('id_vehicle_location')->nullable()->constrained('locations', 'id_location')->restrictOnDelete();
        });

        DB::statement('DROP INDEX IF EXISTS inventory_movements_last_receipt_location');

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->foreignId('id_location')->nullable()->constrained('locations', 'id_location')->restrictOnDelete();
        });

        DB::statement('UPDATE inventory_movements m SET id_location = il.id_location
            FROM inventory_locations il WHERE il.id_inventory_location = m.id_inventory_location');

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropIndex(['id_inventory_location']);
            $table->dropConstrainedForeignId('id_inventory_location');
        });

        DB::statement("CREATE INDEX inventory_movements_last_receipt_location ON inventory_movements (id_warehouse, id_product, id_inventory_movement DESC) WHERE movement_type = 'purchase_receipt' AND id_location IS NOT NULL");

        Schema::dropIfExists('inventory_location_details');
        Schema::dropIfExists('inventory_locations');
        Schema::dropIfExists('vehicle_locations');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('employees');
    }
};
