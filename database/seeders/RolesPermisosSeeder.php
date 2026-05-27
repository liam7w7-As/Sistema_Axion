<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesPermisosSeeder extends Seeder
{
    /**
     * Ejecutar el seeder.
     */
    public function run(): void
    {
        // Limpiar caché de permisos de Spatie
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Crear Permisos (todos en español)
        $permisos = [
            'gestionar_configuracion',
            'gestionar_usuarios',
            'gestionar_ajustes_usuario',
            'gestionar_productos_servicios',
            'gestionar_inventario',
            'gestionar_apertura_caja',
            'gestionar_dashboard_movimientos',
            'gestionar_ventas',
            'anular_ventas',
            'gestionar_cierre_caja',
            'aprobar_cierre_caja',
            'visualizar_ganancias',
            'ver_todos_los_movimientos',
            'ver_reportes_generales'
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        // 2. Crear Roles y asignar permisos
        // Rol: Administrador
        $rolAdmin = Role::firstOrCreate(['name' => 'administrador', 'guard_name' => 'web']);
        // El administrador tiene todos los permisos
        $rolAdmin->syncPermissions(Permission::all());

        // Rol: Vendedor
        $rolVendedor = Role::firstOrCreate(['name' => 'vendedor', 'guard_name' => 'web']);
        // El vendedor tiene permisos limitados (solo sus propias cosas)
        $rolVendedor->syncPermissions([
            'gestionar_ajustes_usuario',
            'gestionar_dashboard_movimientos',
            'gestionar_ventas',
            'gestionar_apertura_caja',
            'gestionar_cierre_caja'
        ]);

        // 3. Asignar roles a los usuarios iniciales (creados en UserSeeder)
        $admin = User::where('codigo', 'ADMIN')->first();
        if ($admin) {
            // Asegurarse de que el admin tenga el rol de Spatie
            $admin->assignRole('administrador');
            // Por consistencia con el campo enum de la base de datos
            $admin->role = 'admin'; 
            $admin->omitir_bloqueo_horario = true;
            $admin->save();
        }

        $vendedor = User::where('codigo', 'VEN001')->first();
        if ($vendedor) {
            $vendedor->assignRole('vendedor');
            // Por consistencia con el campo enum
            $vendedor->role = 'seller';
            $vendedor->save();
        }
    }
}
