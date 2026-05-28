<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetTestsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sistef:reset-tests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reinicia las tablas transaccionales (ventas, cajas, inventario) para hacer pruebas limpias sin borrar productos ni usuarios';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->confirm('¿Estás seguro de que deseas limpiar todas las ventas, cajas, movimientos e historial de inventario? Los productos y usuarios no se borrarán.')) {
            $this->info('Operación cancelada.');
            return;
        }

        $this->info('Iniciando limpieza de pruebas...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Tablas transaccionales a truncar
        $tablas = [
            'sale_items',
            'sales',
            'cash_closures',
            'seller_movements',
            'cash_openings',
            'inventory'
        ];

        foreach ($tablas as $tabla) {
            DB::table($tabla)->truncate();
            $this->line("✔ Tabla '{$tabla}' limpiada.");
        }

        // Poner a 0 el stock actual de todos los productos físicos para reiniciar bien
        DB::table('products_services')->update(['stock_actual' => 0]);
        $this->line("✔ Stock de productos reiniciado a 0.");

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('¡Datos de prueba limpiados exitosamente! Ya puedes realizar la demo o nuevas pruebas desde cero.');
    }
}
