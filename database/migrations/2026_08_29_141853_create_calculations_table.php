<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('design_id')->constrained()->cascadeOnDelete();

            $table->integer('production_time_minutes');
            $table->decimal('packaging_cost', 10, 2)->default(0);
            $table->decimal('margin', 5, 2); // porcentaje aplicado en este cálculo

            $table->decimal('materials_cost', 10, 2);
            $table->decimal('labor_cost', 10, 2);
            $table->decimal('benefits_cost', 10, 2);
            $table->decimal('indirect_cost', 10, 2);
            $table->decimal('total_cost', 10, 2);
            $table->decimal('sale_price', 10, 2);

            $table->date('valid_until'); // vigencia de 5 días calendario
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calculations');
    }
};
