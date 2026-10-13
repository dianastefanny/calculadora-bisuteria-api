<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La unidad pasa a ser texto libre en la base de datos: los valores
     * válidos los define y valida el enum App\Enums\MaterialUnit, lo que
     * permite agregar unidades sin tener que alterar la columna.
     */
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->string('unit', 20)->change();
        });
    }

    public function down(): void
    {
        // Sin reversión: volver al tipo anterior podría truncar o invalidar
        // las unidades ya guardadas.
    }
};
