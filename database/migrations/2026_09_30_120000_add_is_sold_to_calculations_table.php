<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calculations', function (Blueprint $table) {
            // Marca si el usuario ya confirmó que esta pieza se vendió. Solo
            // en ese momento se descuenta el stock de materiales y empaque
            // (no al calcular, para no descontar por simples pruebas de precio).
            $table->boolean('is_sold')->default(false)->after('final_price');
        });
    }

    public function down(): void
    {
        Schema::table('calculations', function (Blueprint $table) {
            $table->dropColumn('is_sold');
        });
    }
};
