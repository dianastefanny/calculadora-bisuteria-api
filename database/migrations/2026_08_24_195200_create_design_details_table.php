<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('design_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 10, 2);
            $table->decimal('subtotal', 10, 2); // quantity * unit_cost del material en ese momento
            $table->timestamps();

            // Evita que un mismo material se repita dos veces en el mismo diseño
            $table->unique(['design_id', 'material_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_details');
    }
};
