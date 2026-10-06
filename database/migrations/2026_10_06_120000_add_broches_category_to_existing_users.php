<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // "Broches" se agregó a las categorías por defecto del registro; esta
    // migración se la agrega también a las cuentas que ya existían.
    public function up(): void
    {
        $userIds = DB::table('users')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('material_categories')
                    ->whereColumn('material_categories.user_id', 'users.id')
                    ->where('material_categories.name', 'Broches');
            })
            ->pluck('id');

        $now = now();

        DB::table('material_categories')->insert(
            $userIds->map(fn ($userId) => [
                'user_id' => $userId,
                'name' => 'Broches',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all()
        );
    }

    public function down(): void
    {
        // Solo borra las categorías "Broches" que no tengan materiales asignados.
        DB::table('material_categories')
            ->where('name', 'Broches')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('materials')
                    ->whereColumn('materials.material_category_id', 'material_categories.id');
            })
            ->delete();
    }
};
