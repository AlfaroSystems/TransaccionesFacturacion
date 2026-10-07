<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Una solicitud de cotización puede reunir varias solicitudes de compra (de distintas
 * sucursales). El vínculo pasa del encabezado al detalle:
 *
 * - purchase_quotation_request_details.id_purchase_quotation_request: cada línea indica
 *   a qué solicitud de cotización pertenece (antes solo se encontraba a través de la
 *   única solicitud de compra del encabezado).
 * - purchase_quotation_requests.id_purchase_request se elimina: las solicitudes de
 *   compra de una cotización son las de sus líneas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_quotation_request_details', function (Blueprint $table) {
            $table->unsignedBigInteger('id_purchase_quotation_request')->nullable();
        });

        // Cada línea toma la solicitud de cotización de su solicitud de compra (la primera,
        // si con el flujo anterior una solicitud de compra se cotizó más de una vez)
        DB::statement('
            UPDATE purchase_quotation_request_details d
               SET id_purchase_quotation_request = (
                   SELECT MIN(qr.id_purchase_quotation_request)
                     FROM purchase_quotation_requests qr
                     JOIN purchase_request_details prd ON prd.id_purchase_request = qr.id_purchase_request
                    WHERE prd.id_purchase_request_detail = d.id_purchase_request_detail
               )
        ');

        // Líneas que no pertenecían a ninguna solicitud de cotización: no se podían consultar
        DB::table('purchase_quotation_request_details')->whereNull('id_purchase_quotation_request')->delete();

        Schema::table('purchase_quotation_request_details', function (Blueprint $table) {
            $table->unsignedBigInteger('id_purchase_quotation_request')->nullable(false)->change();
            $table->foreign('id_purchase_quotation_request')
                ->references('id_purchase_quotation_request')->on('purchase_quotation_requests')
                ->cascadeOnDelete();
        });

        Schema::table('purchase_quotation_requests', function (Blueprint $table) {
            $table->dropForeign(['id_purchase_request']);
            $table->dropColumn('id_purchase_request');
        });
    }

    /**
     * Vuelve a una sola solicitud de compra por cotización (la de sus primeras líneas); la
     * columna queda nullable porque una cotización sin líneas no tiene de dónde tomarla.
     */
    public function down(): void
    {
        Schema::table('purchase_quotation_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('id_purchase_request')->nullable();
        });

        DB::statement('
            UPDATE purchase_quotation_requests qr
               SET id_purchase_request = (
                   SELECT MIN(prd.id_purchase_request)
                     FROM purchase_quotation_request_details d
                     JOIN purchase_request_details prd ON prd.id_purchase_request_detail = d.id_purchase_request_detail
                    WHERE d.id_purchase_quotation_request = qr.id_purchase_quotation_request
               )
        ');

        Schema::table('purchase_quotation_requests', function (Blueprint $table) {
            $table->foreign('id_purchase_request')
                ->references('id_purchase_request')->on('purchase_requests')
                ->restrictOnDelete();
        });

        Schema::table('purchase_quotation_request_details', function (Blueprint $table) {
            $table->dropForeign(['id_purchase_quotation_request']);
            $table->dropColumn('id_purchase_quotation_request');
        });
    }
};
