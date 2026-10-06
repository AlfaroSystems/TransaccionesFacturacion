<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registra el modelo al que pertenece cada cambio: id_record por sí solo no
     * distingue, por ejemplo, una compra de una línea de compra con el mismo ID.
     * Los registros anteriores quedan con el modelo vacío (no se puede deducir).
     */
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('auditable_type')->nullable()->after('user_id');
            $table->index(['auditable_type', 'id_record']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['auditable_type', 'id_record']);
            $table->dropColumn('auditable_type');
        });
    }
};
