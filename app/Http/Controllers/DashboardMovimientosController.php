<?php

namespace App\Http\Controllers;

use App\Models\CashOpening;
use App\Models\SellerDashboard;
use App\Models\SellerMovement;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardMovimientosController extends Controller
{
    /**
     * Muestra el dashboard interactivo del vendedor.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $vendedor_id = null;
        $vendedores_activos = [];
        $vendedores_activos_datos = [];

        // Si es admin, puede seleccionar vendedor o auto-seleccionar el primero
        if ($user->hasRole('administrador')) {
            // Cargar lista de vendedores (usuarios con rol vendedor o admins) que tienen caja abierta
            $vendedores_activos = User::whereHas('aperturasCaja', function ($q) {
                $q->where('status', 'abierta');
            })->orderBy('nombre_completo')->get(['id', 'nombre_completo']);

            // Cargar datos consolidados de todos los vendedores activos
            $vendedores_con_caja = User::habilitados()
                ->whereHas('aperturasCaja', function ($q) {
                    $q->where('status', 'abierta');
                })
                ->with(['aperturasCaja' => function ($q) {
                    $q->where('status', 'abierta')->latest('id');
                }])
                ->get();

            foreach ($vendedores_con_caja as $v) {
                $ap = $v->aperturasCaja->first();
                if ($ap) {
                    $tot_ventas = Sale::where('cash_opening_id', $ap->id)
                        ->where('status', 'completada')
                        ->sum('total');
                    $tot_movimientos = SellerMovement::where('cash_opening_id', $ap->id)->sum('monto');
                    
                    // Obtener la última venta realizada
                    $last_sale = Sale::where('cash_opening_id', $ap->id)
                        ->where('status', 'completada')
                        ->with(['items.productoServicio'])
                        ->latest('id')
                        ->first();
                    
                    $ultima_venta_str = 'Ninguna';
                    if ($last_sale) {
                        $first_item = $last_sale->items->first();
                        if ($first_item && $first_item->productoServicio) {
                            $prod_nombre = $first_item->productoServicio->nombre;
                            $cant_items = $last_sale->items->count();
                            if ($cant_items > 1) {
                                $ultima_venta_str = $prod_nombre . " + " . ($cant_items - 1) . " art. (Bs " . number_format($last_sale->total, 2) . ") - " . $last_sale->created_at->format('H:i');
                            } else {
                                $ultima_venta_str = $prod_nombre . " (Bs " . number_format($last_sale->total, 2) . ") - " . $last_sale->created_at->format('H:i');
                            }
                        } else {
                            $ultima_venta_str = "Venta #" . $last_sale->id . " (Bs " . number_format($last_sale->total, 2) . ") - " . $last_sale->created_at->format('H:i');
                        }
                    }

                    $vendedores_activos_datos[] = [
                        'id' => $v->id,
                        'nombre_completo' => $v->nombre_completo,
                        'codigo' => $v->codigo,
                        'saldo_inicial' => $ap->saldo_inicial,
                        'total_ventas' => $tot_ventas,
                        'total_movimientos' => $tot_movimientos,
                        'saldo_esperado' => $ap->saldo_inicial + $tot_ventas + $tot_movimientos,
                        'ultima_venta' => $ultima_venta_str
                    ];
                }
            }

            if ($request->has('vendedor_id') && $request->vendedor_id) {
                $vendedor_id = $request->vendedor_id;
            } elseif ($vendedores_activos->count() > 0) {
                $vendedor_id = $vendedores_activos->first()->id;
            }
        } else {
            // Vendedor solo puede verse a sí mismo
            $vendedor_id = $user->id;
        }

        if (!$vendedor_id) {
            return Inertia::render('Dashboard/Index', [
                'apertura_activa' => false,
                'vendedores_activos' => $vendedores_activos,
                'vendedores_activos_datos' => $vendedores_activos_datos,
                'vendedor_seleccionado_id' => null,
            ]);
        }

        $vendedor = User::find($vendedor_id);

        // Buscar caja abierta
        $apertura = CashOpening::where('user_id', $vendedor_id)
            ->where('status', 'abierta')
            ->first();

        if (!$apertura) {
            return Inertia::render('Dashboard/Index', [
                'apertura_activa' => false,
                'vendedores_activos' => $vendedores_activos,
                'vendedores_activos_datos' => $vendedores_activos_datos,
                'vendedor_seleccionado_id' => $vendedor_id,
            ]);
        }

        // CÁLCULO DE SALDOS
        $saldo_inicial = $apertura->saldo_inicial;
        
        // Total Ventas Completadas de esta apertura
        $total_ventas = Sale::where('cash_opening_id', $apertura->id)
            ->where('status', 'completada')
            ->sum('total');

        // Total Movimientos (manuales) de esta apertura
        $total_movimientos = SellerMovement::where('cash_opening_id', $apertura->id)->sum('monto');

        // Saldo Actual Esperado
        $saldo_actual_esperado = $saldo_inicial + $total_ventas + $total_movimientos;
        $limite_venta = $apertura->limite_venta;
        
        // Estado Caja
        $cierre_pendiente = $apertura->cierreCaja()->where('status', 'pendiente')->exists();
        $cierre_aprobado = $apertura->cierreCaja()->where('status', 'aprobado')->exists();
        
        $estado_caja = 'Abierta';
        if ($cierre_aprobado) $estado_caja = 'Cerrada y Aprobada';
        elseif ($cierre_pendiente) $estado_caja = 'Cierre Pendiente';

        // Obtener la suma de movimientos manuales por sección
        $movimientos_por_seccion = SellerMovement::where('cash_opening_id', $apertura->id)
            ->select('seccion', DB::raw('SUM(monto) as total_seccion'))
            ->groupBy('seccion')
            ->pluck('total_seccion', 'seccion')
            ->toArray();

        // Obtener la suma de ventas por sección (Ventas Rápidas del Dashboard y Ventas del Carrito)
        $ventas_por_seccion = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products_services', 'sale_items.product_service_id', '=', 'products_services.id')
            ->where('sales.cash_opening_id', $apertura->id)
            ->where('sales.status', 'completada')
            ->select('products_services.seccion_reporte as seccion', DB::raw('SUM(sale_items.subtotal) as total_seccion'))
            ->whereNotNull('products_services.seccion_reporte')
            ->groupBy('products_services.seccion_reporte')
            ->pluck('total_seccion', 'seccion')
            ->toArray();

        // Inicializar las 10 secciones en 0 si no existen
        $secciones_ers = [
            'tarjetas_unidad', 'tarjetas_mayor', 'recuperaciones', 'chips', 
            'recargas', 'megas', 'servicios_digitales', 'banca_digital', 
            'servicio_tecnico', 'efectivo_monedas'
        ];

        // Consolidar totales
        $totales_consolidados = [];
        foreach ($secciones_ers as $sec) {
            $total_movs = $movimientos_por_seccion[$sec] ?? 0;
            $total_ventas_sec = $ventas_por_seccion[$sec] ?? 0;
            $totales_consolidados[$sec] = $total_movs + $total_ventas_sec;
        }

        // CÁLCULO DE LÍMITES Y SALDOS DE SERVICIOS
        $limites_json = $apertura->limites_servicios_json ?? [];
        $saldos_servicios = [];
        
        $movs_por_servicio = SellerMovement::where('cash_opening_id', $apertura->id)
            ->select('seccion', 'operador', DB::raw('SUM(monto) as total_usado'))
            ->groupBy('seccion', 'operador')
            ->get();

        foreach ($limites_json as $key => $limite) {
            if ($limite === null || $limite === '') continue; // Ilimitado

            $usado = 0;
            if (str_starts_with($key, 'recargas_') || str_starts_with($key, 'megas_')) {
                $partes = explode('_', $key, 2);
                $seccion = $partes[0];
                $operador = $partes[1];
                $mov = $movs_por_servicio->where('seccion', $seccion)->where('operador', $operador)->first();
                $usado = $mov ? $mov->total_usado : 0;
            } else {
                $usado = $movs_por_servicio->where('seccion', $key)->sum('total_usado');
            }

            $saldos_servicios[$key] = [
                'limite' => (float) $limite,
                'usado' => (float) $usado,
                'disponible' => max(0, (float) $limite - (float) $usado)
            ];
        }

        return Inertia::render('Dashboard/Index', [
            'apertura_activa' => true,
            'datos_jornada' => [
                'vendedor' => $vendedor->nombre_completo,
                'apertura_id' => $apertura->id,
                'fecha' => $apertura->created_at->format('d/m/Y H:i'),
                'saldo_inicial' => $saldo_inicial,
                'limite_venta' => $limite_venta,
                'total_ventas' => $total_ventas,
                'total_movimientos' => $total_movimientos,
                'saldo_actual_esperado' => $saldo_actual_esperado,
                'estado_caja' => $estado_caja,
                'servicios_asignados' => $vendedor->servicios_asignados_json ?? [],
            ],
            'movimientos_por_seccion' => $totales_consolidados,
            'saldos_servicios' => $saldos_servicios,
            'vendedores_activos' => $vendedores_activos,
            'vendedores_activos_datos' => $vendedores_activos_datos,
            'vendedor_seleccionado_id' => $vendedor_id,
            'cierre_aprobado' => $cierre_aprobado,
            'productos_dashboard' => \App\Models\ProductService::where('estado', 'activo')->get(),
        ]);
    }

    /**
     * Guarda un movimiento individual puramente monetario (ej. Efectivo/Monedas)
     */
    public function guardarMovimiento(Request $request)
    {
        $validated = $request->validate([
            'cash_opening_id' => 'required|exists:cash_openings,id',
            'seccion' => 'required|string|in:tarjetas_unidad,tarjetas_mayor,recuperaciones,chips,recargas,megas,servicios_digitales,banca_digital,servicio_tecnico,efectivo_monedas',
            'operador' => 'nullable|string',
            'cantidad' => 'nullable|integer',
            'monto' => 'required|numeric',
            'observacion' => 'nullable|string|max:500'
        ], [
            'monto.required' => 'El monto es obligatorio.',
            'monto.numeric' => 'El monto debe ser un valor numérico.'
        ]);
        
        $apertura = CashOpening::findOrFail($validated['cash_opening_id']);

        // Verificaciones de seguridad
        if ($apertura->status !== 'abierta') {
            return redirect()->back()->with('error', 'La caja no se encuentra abierta.');
        }

        if ($apertura->cierreCaja && $apertura->cierreCaja->status === 'aprobado') {
            return redirect()->back()->with('error', 'No se permiten modificaciones después del cierre de caja.');
        }

        if (in_array($validated['seccion'], ['recargas', 'megas']) && empty($validated['operador'])) {
            return redirect()->back()->withErrors(['operador' => 'Debe seleccionar un operador.']);
        }

        // Verificar límite
        $key = in_array($validated['seccion'], ['recargas', 'megas']) 
            ? $validated['seccion'] . '_' . ($validated['operador'] ?? '') 
            : $validated['seccion'];

        $limites = $apertura->limites_servicios_json ?? [];
        $disponible = null;
        
        if (array_key_exists($key, $limites) && $limites[$key] !== null && $limites[$key] !== '') {
            $limiteAsignado = (float) $limites[$key];
            
            $usado = SellerMovement::where('cash_opening_id', $apertura->id)
                ->where('seccion', $validated['seccion'])
                ->when($validated['operador'] ?? null, fn($q, $op) => $q->where('operador', $op))
                ->sum('monto');
                
            $disponible = max(0, $limiteAsignado - $usado);
            
            if ($validated['monto'] > $disponible) {
                return redirect()->back()->withErrors(['monto' => "Saldo insuficiente para este servicio. Disponible: {$disponible} Bs"]);
            }
        }

        DB::beginTransaction();
        try {
            // Guardar historial detallado
            SellerMovement::create([
                'cash_opening_id' => $apertura->id,
                'seccion' => $validated['seccion'],
                'operador' => $validated['operador'] ?? null,
                'cantidad' => $validated['cantidad'],
                'monto' => $validated['monto'],
                'observacion' => $validated['observacion'],
            ]);

            $restante = $disponible !== null ? ($disponible - $validated['monto']) : 'Ilimitado';
            $mensaje = $restante === 'Ilimitado' ? 'Movimiento registrado exitosamente.' : "✅ Movimiento registrado. Disponible: {$restante} Bs";

            DB::commit();
            return redirect()->back()->with('success', $mensaje);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al guardar el movimiento: ' . $e->getMessage());
        }
    }

    /**
     * Guarda una Venta Rápida desde el Dashboard.
     */
    public function guardarVentaRapida(Request $request)
    {
        $validated = $request->validate([
            'cash_opening_id' => 'required|exists:cash_openings,id',
            'product_service_id' => 'required|exists:products_services,id',
            'cantidad' => 'required|integer|min:1',
            'observacion' => 'nullable|string|max:500'
        ]);

        $apertura = CashOpening::findOrFail($validated['cash_opening_id']);

        if ($apertura->status !== 'abierta') {
            return redirect()->back()->with('error', 'La caja no se encuentra abierta.');
        }

        if ($apertura->cierreCaja && $apertura->cierreCaja->status === 'aprobado') {
            return redirect()->back()->with('error', 'No se permiten modificaciones después del cierre de caja.');
        }

        DB::beginTransaction();
        try {
            $producto = \App\Models\ProductService::findOrFail($validated['product_service_id']);
            
            // Validar stock si es producto físico
            if ($producto->tipo === 'producto') {
                if ($producto->stock_actual < $validated['cantidad']) {
                    throw new \Exception("Stock insuficiente para '{$producto->nombre}'. Disponible: {$producto->stock_actual}");
                }
            }

            // Generar Código Único de Venta
            $ultimoFolio = \App\Models\Sale::withTrashed()->max('id') ?? 0;
            $codigo = 'VEN-' . str_pad($ultimoFolio + 1, 6, '0', STR_PAD_LEFT);

            // Crear Venta
            $subtotal = $producto->precio_venta * $validated['cantidad'];
            $venta = \App\Models\Sale::create([
                'codigo' => $codigo,
                'user_id' => Auth::id(),
                'cliente_nombre' => 'Cliente General', // Valor por defecto
                'cash_opening_id' => $apertura->id,
                'fecha_hora' => \Carbon\Carbon::now(),
                'total' => $subtotal,
                'tipo_pago' => 'efectivo', // Asumimos efectivo para ventas rápidas en caja
                'observacion' => 'Venta rápida desde Dashboard. ' . ($validated['observacion'] ?? ''),
                'status' => 'completada',
            ]);

            // Crear Ítem de Venta
            \App\Models\SaleItem::create([
                'sale_id' => $venta->id,
                'product_service_id' => $producto->id,
                'cantidad' => $validated['cantidad'],
                'precio_venta' => $producto->precio_venta,
                'precio_compra' => $producto->precio_compra,
                'subtotal' => $subtotal,
            ]);

            // Descontar Stock e Inventario
            if ($producto->tipo === 'producto') {
                $producto->decrement('stock_actual', $validated['cantidad']);

                \App\Models\Inventory::create([
                    'product_service_id' => $producto->id,
                    'tipo_inventario' => 'fisico',
                    'cantidad_ingreso' => $validated['cantidad'],
                    'stock_actual' => $producto->fresh()->stock_actual,
                    'stock_previsto' => $producto->fresh()->stock_actual,
                    'precio_compra' => $producto->precio_compra,
                    'fecha_hora' => \Carbon\Carbon::now(),
                    'tipo_movimiento' => 'egreso',
                    'observacion' => 'Descuento por venta rápida ' . $codigo,
                ]);
            }

            DB::commit();
            // Retornamos la respuesta con la redirección al detalle de la venta
            return redirect()->route('ventas.show', $venta->id)->with([
                'success' => 'Venta rápida registrada exitosamente.',
                'ticket_url' => route('ventas.imprimir', $venta->id)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al procesar la venta: ' . $e->getMessage());
        }
    }
}
