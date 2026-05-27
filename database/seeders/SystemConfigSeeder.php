<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemConfig;

/**
 * Seeder: Configuración inicial del sistema.
 * Carga los datos base de configuración según el ERS.
 */
class SystemConfigSeeder extends Seeder
{
    /**
     * Ejecutar el seeder.
     */
    public function run(): void
    {
        SystemConfig::create([
            'nombre_sistema' => 'SISTEMA DE VENTAS DE ARTÍCULOS DE TELEFONÍA',
            'alias' => 'Sistema Telefonía',
            'logo' => null,
            'actividad' => 'Venta de artículos y servicios de telefonía',
            'moneda' => 'Bs',
            'hora_inicio_admin' => '08:00',
            'hora_fin_admin' => '18:00',
            'hora_inicio_vendedor' => '08:00',
            'hora_fin_vendedor' => '18:00',
            'formato_impresion' => 'thermal_58mm',
            'tamano_papel_thermal' => '58mm',
        ]);
    }
}
