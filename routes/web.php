<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Middleware\VerificarHorarioAcceso;

/**
 * Rutas web del sistema de telefonía.
 * La ruta raíz redirige al login o al dashboard según autenticación.
 */

// Redirigir raíz al dashboard (si autenticado) o al login
Route::get('/', function () {
    return redirect()->route('login');
});

// Rutas protegidas (Requieren autenticación y verificación de horario)
Route::middleware(['auth', VerificarHorarioAcceso::class])->group(function () {
    
    // Módulo: Ajustes de Usuario (Personal)
    Route::get('/ajustes-usuario', [App\Http\Controllers\AjustesUsuarioController::class, 'index'])->name('ajustes-usuario.index');
    Route::post('/ajustes-usuario', [App\Http\Controllers\AjustesUsuarioController::class, 'update'])->name('ajustes-usuario.update');

    // Módulo: Configuración del Sistema
    Route::middleware('can:gestionar_configuracion')->group(function () {
        Route::get('/configuracion', [App\Http\Controllers\ConfiguracionController::class, 'index'])->name('configuracion.index');
        Route::post('/configuracion', [App\Http\Controllers\ConfiguracionController::class, 'update'])->name('configuracion.update');
    });

    // Módulo: Gestión de Usuarios
    Route::middleware('can:gestionar_usuarios')->group(function () {
        Route::resource('usuarios', App\Http\Controllers\UsuarioController::class)->except(['show']);
    });

    // Rutas de perfil de usuario (Laravel Breeze / Default)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Módulo: Productos y Servicios
    Route::middleware('can:gestionar_productos_servicios')->group(function () {
        Route::resource('productos-servicios', App\Http\Controllers\ProductoServicioController::class)->parameters([
            'productos-servicios' => 'productoServicio'
        ]);
    });

    // Módulo: Inventario
    Route::middleware('can:gestionar_inventario')->group(function () {
        Route::resource('inventario', App\Http\Controllers\InventarioController::class);
    });

    // Módulo: Apertura de Caja
    Route::middleware('can:gestionar_apertura_caja')->group(function () {
        Route::resource('aperturas-caja', App\Http\Controllers\AperturaCajaController::class)->parameters([
            'aperturas-caja' => 'aperturas_caja'
        ]);
    });

    // Módulo: Cierre de Caja
    Route::middleware('can:gestionar_cierre_caja')->group(function () {
        Route::get('cierres-caja', [App\Http\Controllers\CierreCajaController::class, 'index'])->name('cierres-caja.index');
        Route::get('cierres-caja/create', [App\Http\Controllers\CierreCajaController::class, 'create'])->name('cierres-caja.create');
        Route::post('cierres-caja', [App\Http\Controllers\CierreCajaController::class, 'store'])->name('cierres-caja.store');
        Route::get('cierres-caja/{cierre_caja}', [App\Http\Controllers\CierreCajaController::class, 'show'])->name('cierres-caja.show');
        Route::get('cierres-caja/{cierre_caja}/imprimir', [App\Http\Controllers\CierreCajaController::class, 'imprimir'])->name('cierres-caja.imprimir');
    });

    Route::middleware('can:aprobar_cierre_caja')->group(function () {
        Route::post('cierres-caja/{cierre_caja}/aprobar', [App\Http\Controllers\CierreCajaController::class, 'aprobar'])->name('cierres-caja.aprobar');
    });

    // Módulo: Dashboard y Movimientos Vendedor
    Route::middleware('can:gestionar_dashboard_movimientos')->group(function () {
        Route::get('dashboard', [App\Http\Controllers\DashboardMovimientosController::class, 'index'])->name('dashboard');
        Route::post('dashboard/movimiento', [App\Http\Controllers\DashboardMovimientosController::class, 'guardarMovimiento'])->name('dashboard.guardar_movimiento');
        Route::post('dashboard/venta-rapida', [App\Http\Controllers\DashboardMovimientosController::class, 'guardarVentaRapida'])->name('dashboard.guardar_venta_rapida');
    });
    // Módulo: Ventas
    Route::middleware('can:gestionar_ventas')->group(function () {
        Route::get('ventas', [App\Http\Controllers\VentaController::class, 'index'])->name('ventas.index');
        Route::get('ventas/create', [App\Http\Controllers\VentaController::class, 'create'])->name('ventas.create');
        Route::post('ventas', [App\Http\Controllers\VentaController::class, 'store'])->name('ventas.store');
        Route::get('ventas/{venta}', [App\Http\Controllers\VentaController::class, 'show'])->name('ventas.show');
        Route::get('ventas/{venta}/imprimir', [App\Http\Controllers\VentaController::class, 'imprimir'])->name('ventas.imprimir');
    });

    Route::middleware('can:anular_ventas')->group(function () {
        Route::post('ventas/{venta}/anular', [App\Http\Controllers\VentaController::class, 'anular'])->name('ventas.anular');
    });

    // Módulo: Reportes y Auditoría
    Route::middleware('can:ver_reportes_generales')->group(function () {
        Route::get('/auditoria', [App\Http\Controllers\AuditoriaController::class, 'index'])->name('auditoria.index');
        
        Route::prefix('reportes')->name('reportes.')->group(function () {
            Route::get('/usuarios', [App\Http\Controllers\ReporteController::class, 'usuarios'])->name('usuarios');
        Route::get('/productos', [App\Http\Controllers\ReporteController::class, 'productos'])->name('productos');
        Route::get('/ventas', [App\Http\Controllers\ReporteController::class, 'ventas'])->name('ventas');
        Route::get('/movimientos', [App\Http\Controllers\ReporteController::class, 'movimientos'])->name('movimientos');
        Route::get('/caja', [App\Http\Controllers\ReporteController::class, 'caja'])->name('caja');
        Route::get('/saldo-servicios', [App\Http\Controllers\ReporteController::class, 'saldoServicios'])->name('saldo-servicios');
        Route::get('/inventario', [App\Http\Controllers\ReporteController::class, 'inventario'])->name('inventario');
        
        // Rutas compartidas de exportación
        Route::match(['get', 'post'], '/exportar-pdf/{tipo}', [App\Http\Controllers\ReporteController::class, 'exportarPdf'])->name('exportar-pdf');
        Route::match(['get', 'post'], '/exportar-excel/{tipo}', [App\Http\Controllers\ReporteController::class, 'exportarExcel'])->name('exportar-excel');
    });

    Route::middleware('can:visualizar_ganancias')->prefix('reportes')->name('reportes.')->group(function () {
        Route::get('/ganancias', [App\Http\Controllers\ReporteController::class, 'ganancias'])->name('ganancias');
    });

    }); // Cierre de can:ver_reportes_generales

});

require __DIR__.'/auth.php';
