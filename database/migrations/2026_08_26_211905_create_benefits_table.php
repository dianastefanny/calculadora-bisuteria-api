<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('benefits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('benefit_type_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150); // ej: "Salud 8.5%"
            $table->decimal('percentage', 5, 2); // ej: 8.50 = 8.5%
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('benefits');
    }
};
