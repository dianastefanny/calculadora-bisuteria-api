<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calculations', function (Blueprint $table) {
            // Cuántas piezas se cotizaron de una vez (por defecto 1, para no
            // afectar los cálculos ya guardados antes de este cambio).
            $table->integer('quantity')->default(1)->after('production_time_minutes');

            // Descuento opcional por pedidos grandes, y el precio ya con ese
            // descuento aplicado. sale_price sigue siendo el precio sugerido
            // SIN descuento, para poder mostrar los dos.
            $table->decimal('discount_percentage', 5, 2)->nullable()->after('margin');
            $table->decimal('final_price', 10, 2)->nullable()->after('sale_price');
        });
    }

    public function down(): void
    {
        Schema::table('calculations', function (Blueprint $table) {
            $table->dropColumn(['quantity', 'discount_percentage', 'final_price']);
        });
    }
};
