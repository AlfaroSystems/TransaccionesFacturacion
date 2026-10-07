<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contadores de los correlativos de documentos (ver App\Support\DocumentSequence).
 *
 * Antes cada código se calculaba leyendo el último documento: dos creados a la vez
 * obtenían el mismo número y el segundo fallaba contra el índice único.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->string('prefix', 50)->primary();
            $table->unsignedBigInteger('last_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sequences');
    }
};
