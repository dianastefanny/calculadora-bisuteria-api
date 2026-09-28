<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('password_reset_codes', function (Blueprint $table) {
            // El código se guarda hasheado (Hash::make), no en texto plano —
            // un hash de bcrypt ocupa unos 60 caracteres, no 6.
            $table->string('code', 255)->change();
        });
    }

    public function down(): void
    {
        Schema::table('password_reset_codes', function (Blueprint $table) {
            $table->string('code', 6)->change();
        });
    }
};
