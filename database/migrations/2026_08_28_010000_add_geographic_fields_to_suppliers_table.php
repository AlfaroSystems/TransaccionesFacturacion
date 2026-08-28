<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('address')->constrained('departments')->nullOnDelete();
            $table->foreignId('municipality_id')->nullable()->after('department_id')->constrained('municipalities')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->after('municipality_id')->constrained('districts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['municipality_id']);
            $table->dropForeign(['district_id']);
            $table->dropColumn(['department_id', 'municipality_id', 'district_id']);
        });
    }
};
