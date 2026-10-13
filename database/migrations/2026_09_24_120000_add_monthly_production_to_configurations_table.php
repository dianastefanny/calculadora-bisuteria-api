<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            // Opcional. Con valor, los costos indirectos se reparten por pieza;
            // en null (por defecto) se reparten por minuto de mano de obra
            // (ver CalculationController::store).
            $table->decimal('monthly_production', 10, 2)->nullable()->after('monthly_working_hours');
        });
    }

    public function down(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            $table->dropColumn('monthly_production');
        });
    }
};
