<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calculations', function (Blueprint $table) {
            $table->foreignId('packaging_id')->nullable()->after('design_id')->constrained()->nullOnDelete();
            $table->decimal('packaging_quantity', 10, 2)->default(1)->after('packaging_id');
        });
    }

    public function down(): void
    {
        Schema::table('calculations', function (Blueprint $table) {
            $table->dropForeign(['packaging_id']);
            $table->dropColumn(['packaging_id', 'packaging_quantity']);
        });
    }
};
