<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder: Usuarios iniciales del sistema.
 * Crea un administrador y un vendedor de prueba.
 */
class UserSeeder extends Seeder
{
    /**
     * Ejecutar el seeder.
     */
    public function run(): void
    {
        // Administrador del sistema
        User::create([
            'nombre_completo' => 'Administrador',
            'codigo' => 'ADMIN',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'foto' => null,
            'estado' => 'habilitado',
            'servicios_asignados_json' => null,
            'visualizar_ganancias' => true,
        ]);

        // Vendedor de prueba
        User::create([
            'nombre_completo' => 'Vendedor Prueba',
            'codigo' => 'VEN001',
            'password' => Hash::make('password123'),
            'role' => 'seller',
            'foto' => null,
            'estado' => 'habilitado',
            'servicios_asignados_json' => null,
            'visualizar_ganancias' => false,
        ]);
    }
}
