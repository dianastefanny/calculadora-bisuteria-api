<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('currency', 10)->default('COP');
            $table->decimal('monthly_production', 10, 2)->default(0);
            $table->decimal('default_margin', 5, 2)->default(30); // porcentaje, ej: 30.00 = 30%
            $table->string('theme', 20)->default('light');

            // Necesarios para calcular costo de mano de obra por minuto
            $table->decimal('monthly_salary', 10, 2)->default(0);
            $table->decimal('monthly_working_hours', 6, 2)->default(0);
            $table->decimal('monthly_productive_minutes', 8, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configurations');
    }
};
