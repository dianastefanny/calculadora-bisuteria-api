<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Se retira la producción mensual obligatoria (con valor por defecto 0).
     * Se reintroduce como campo opcional y nulo en la migración
     * 2026_09_24_120000_add_monthly_production_to_configurations_table.
     */
    public function up(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            $table->dropColumn('monthly_production');
        });
    }

    public function down(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            $table->decimal('monthly_production', 10, 2)->default(0);
        });
    }
};
