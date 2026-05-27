<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Seeder principal: Ejecuta todos los seeders del sistema.
 * Orden de ejecución respeta las dependencias de integridad referencial.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Ejecutar los seeders de la aplicación.
     */
    public function run(): void
    {
        $this->call([
            SystemConfigSeeder::class,  // 1. Configuración del sistema
            UserSeeder::class,          // 2. Usuarios iniciales
            RolesPermisosSeeder::class, // 3. Roles y permisos de Spatie
        ]);
    }
}
