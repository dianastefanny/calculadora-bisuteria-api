<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * No siembra usuarios de prueba: una cuenta con correo y contraseña
     * conocidos sería un riesgo si se ejecuta "db:seed" en un servidor real.
     * Cada usuario crea su cuenta desde la app (POST /register).
     */
    public function run(): void
    {
        //
    }
}
