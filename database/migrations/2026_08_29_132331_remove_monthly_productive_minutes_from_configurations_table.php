<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los minutos productivos dejan de guardarse: ahora se derivan de las
     * horas laborales en el accessor Configuration::monthlyProductiveMinutes,
     * lo que evita que ambos valores queden desincronizados.
     */
    public function up(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            $table->dropColumn('monthly_productive_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            $table->decimal('monthly_productive_minutes', 8, 2)->default(0);
        });
    }
};
