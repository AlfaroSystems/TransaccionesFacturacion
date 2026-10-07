<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El código de ubicación (p. ej. A-1-1-1: pasillo, rack, nivel y posición) era único en
 * todo el sistema, por lo que dos bodegas no podían tener la misma ubicación física y la
 * generación masiva fallaba en la segunda bodega. Pasa a ser único por bodega.
 *
 * down() vuelve a exigirlo en todo el sistema: falla si ya hay códigos repetidos en
 * distintas bodegas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unique(['id_warehouse', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropUnique(['id_warehouse', 'code']);
            $table->unique('code');
        });
    }
};
