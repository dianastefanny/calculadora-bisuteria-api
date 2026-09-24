<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            // Opcional: si el usuario sabe cuántas piezas produce al mes,
            // los costos indirectos se reparten por pieza en vez de por
            // minuto de mano de obra (ver CalculationController@store).
            // Nula por defecto = seguir usando el reparto por minutos.
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
