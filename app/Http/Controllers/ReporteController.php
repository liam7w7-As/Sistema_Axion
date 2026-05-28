<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\ProductService;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\CashClosure;
use App\Models\SellerMovement;
use App\Services\PdfReportService;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReporteController extends Controller
{
    // ==========================================
    // VISTAS INERTIA
    // ==========================================
    
    public function usuarios(Request $request) { 
        return Inertia::render('Reportes/Usuarios', ['datos' => $this->construirQuery('usuarios', $request)->paginate(15)->withQueryString()]); 
    }
    public function productos(Request $request) { 
        return Inertia::render('Reportes/Productos', ['datos' => $this->construirQuery('productos', $request)->paginate(15)->withQueryString()]); 
    }
    public function ventas(Request $request) { 
        return Inertia::render('Reportes/Ventas', ['datos' => $this->construirQuery('ventas', $request)->paginate(15)->withQueryString()]); 
    }
    public function movimientos(Request $request) { 
        return Inertia::render('Reportes/Movimientos', ['datos' => $this->construirQuery('movimientos', $request)->paginate(15)->withQueryString()]); 
    }
    public function caja(Request $request) { 
        return Inertia::render('Reportes/Caja', ['datos' => $this->construirQuery('caja', $request)->paginate(15)->withQueryString()]); 
    }
    public function saldoServicios(Request $request) { 
        return Inertia::render('Reportes/SaldoServicios', ['datos' => $this->construirQuery('saldos', $request)]); 
    }
    public function ganancias(Request $request) { 
        return Inertia::render('Reportes/Ganancias', ['datos' => $this->construirQuery('ganancias', $request)->paginate(15)->withQueryString()]); 
    }
    public function inventario(Request $request) { 
        return Inertia::render('Reportes/Inventario', ['datos' => $this->construirQuery('inventario', $request)->paginate(15)->withQueryString()]); 
    }

    // ==========================================
    // LÓGICA DE EXPORTACIÓN
    // ==========================================

    public function exportarExcel(Request $request, $tipo)
    {
        $className = 'App\\Exports\\' . ucfirst($tipo) . 'Export';
        if (class_exists($className)) {
            return Excel::download(new $className($request), 'reporte_'.$tipo.'_'.date('Ymd').'.xlsx');
        }
        
        abort(404, "Exportación Excel para {$tipo} no implementada.");
    }

    public function exportarPdf(Request $request, $tipo)
    {
        $query = $this->construirQuery($tipo, $request);
        $datos = $query instanceof \Illuminate\Support\Collection ? $query : $query->get();
        
        $orientacion = $request->input('orientacion', 'P'); // Por defecto Vertical Carta
        $titulos = [
            'usuarios' => 'LISTA DE USUARIOS.[ADM WEB]',
            'productos' => 'LISTA DE PRODUCTOS.[ADM WEB]',
            'ventas' => 'REPORTE DE VENTAS.[WEB]',
            'movimientos' => 'MOVIMIENTOS POR VENDEDOR.[WEB]',
            'caja' => 'APERTURA Y CIERRE DE CAJA.[WEB]',
            'saldos' => 'SALDO GENERAL POR SERVICIOS.[ADM WEB]',
            'ganancias' => 'REPORTE DE GANANCIAS.[ADM WEB]',
            'inventario' => 'REPORTE DE INVENTARIO.[ADM WEB]',
            'auditoria' => 'REPORTE DE AUDITORÍA.[ADM WEB]',
        ];

        $pdf = new PdfReportService($orientacion, $titulos[$tipo] ?? 'REPORTE');

        // Construir tabla HTML (simplificada para TCPDF)
        $html = $this->generarHtmlTabla($tipo, $datos);
        $pdf->renderHtmlTable($html);

        $pdfContent = $pdf->Output('reporte_'.$tipo.'.pdf', 'S');

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="reporte_'.$tipo.'.pdf"');
    }

    // ==========================================
    // QUERY BUILDER Y FILTROS
    // ==========================================

    public function construirQuery($tipo, Request $request)
    {
        $user = Auth::user();
        $isVendedor = $user->hasRole('vendedor');
        
        // Obtener vendedor_id del request, pero forzar el ID del auth si es vendedor
        $vendedor_id = $isVendedor ? $user->id : $request->input('vendedor_id');

        $fecha_inicio = $request->input('fecha_inicio');
        $fecha_fin = $request->input('fecha_fin');
        if ($fecha_inicio && $fecha_fin) {
            $fecha_inicio = Carbon::parse($fecha_inicio)->startOfDay();
            $fecha_fin = Carbon::parse($fecha_fin)->endOfDay();
        }

        switch ($tipo) {
            case 'usuarios':
                return User::with('roles')
                    ->when($request->rol, fn($q) => $q->role($request->rol))
                    ->when($request->estado, fn($q) => $q->where('estado', $request->estado));

            case 'productos':
                return ProductService::query()
                    ->when($request->tipo_registro, fn($q) => $q->where('tipo', $request->tipo_registro))
                    ->when($request->operador, fn($q) => $q->where('operador', $request->operador))
                    ->when($request->categoria, fn($q) => $q->where('categoria', 'like', "%{$request->categoria}%"))
                    ->when($request->estado, fn($q) => $q->where('estado', $request->estado));

            case 'ventas':
                return Sale::with(['items.productoServicio', 'usuario'])
                    ->when($vendedor_id, fn($q) => $q->where('user_id', $vendedor_id))
                    ->when($request->tipo_pago, fn($q) => $q->where('tipo_pago', $request->tipo_pago))
                    ->when($request->estado, fn($q) => $q->where('status', $request->estado))
                    ->when($fecha_inicio && $fecha_fin, fn($q) => $q->whereBetween('fecha_hora', [$fecha_inicio, $fecha_fin]));

            case 'movimientos':
                return SellerMovement::with('aperturaCaja.usuario')
                    ->when($vendedor_id, fn($q) => $q->whereHas('aperturaCaja', fn($q2) => $q2->where('user_id', $vendedor_id)))
                    ->when($request->seccion, fn($q) => $q->where('seccion', $request->seccion))
                    ->when($request->operador, fn($q) => $q->where('operador', $request->operador))
                    ->when($fecha_inicio && $fecha_fin, fn($q) => $q->whereBetween('created_at', [$fecha_inicio, $fecha_fin]));

            case 'caja':
                return CashClosure::with(['aperturaCaja.usuario', 'aprobadoPor'])
                    ->when($vendedor_id, fn($q) => $q->whereHas('aperturaCaja', fn($q2) => $q2->where('user_id', $vendedor_id)))
                    ->when($request->estado, fn($q) => $q->where('status', $request->estado))
                    ->when($fecha_inicio && $fecha_fin, fn($q) => $q->whereBetween('fecha_hora_cierre', [$fecha_inicio, $fecha_fin]));

            case 'saldos':
                if (!$user->can('ver_reportes_generales') || !$user->hasRole('administrador')) {
                    abort(403, 'Solo administradores pueden ver este reporte.');
                }
                
                $cierres = \App\Models\CashClosure::with(['aperturaCaja.usuario', 'aperturaCaja.sellerMovements'])
                    ->when($vendedor_id, fn($q) => $q->whereHas('aperturaCaja', fn($q2) => $q2->where('user_id', $vendedor_id)))
                    ->when($fecha_inicio && $fecha_fin, fn($q) => $q->whereBetween('fecha_hora_cierre', [$fecha_inicio, $fecha_fin]))
                    ->get();
                    
                return $cierres->flatMap(function($cierre) use ($request) {
                    $apertura = $cierre->aperturaCaja;
                    $limites = $apertura->limites_servicios_json ?? [];
                    $resultados = collect();
                    
                    foreach ($limites as $key => $limite) {
                        if ($limite === null || $limite === '') continue;
                        
                        $parts = explode('_', $key, 2);
                        $seccion = $parts[0];
                        $operador = $parts[1] ?? null;
                        
                        // Filtros
                        if ($request->seccion && $request->seccion !== $seccion) continue;
                        if ($request->operador && $request->operador !== $operador) continue;
                        
                        $usado = $apertura->sellerMovements
                            ->where('seccion', $seccion)
                            ->where('operador', $operador)
                            ->sum('monto');
                            
                        $resultados->push((object)[
                            'fecha' => Carbon::parse($cierre->fecha_hora_cierre)->format('d/m/Y'),
                            'vendedor' => $apertura->usuario->nombre_completo,
                            'servicio' => $seccion,
                            'operador' => $operador ?: 'N/A',
                            'limite_asignado' => (float)$limite,
                            'total_operado' => $usado,
                            'disponible' => max(0, (float)$limite - $usado),
                            'saldo_inicial' => $apertura->saldo_inicial,
                            'saldo_esperado' => $cierre->saldo_esperado,
                            'entregado' => $cierre->saldo_entregado,
                            'diferencia' => $cierre->diferencia
                        ]);
                    }
                    return $resultados;
                })->values();

            case 'ganancias':
                if (!$user->can('visualizar_ganancias')) {
                    abort(403, 'No tiene permisos para visualizar ganancias.');
                }
                
                // PARTE 1: Ventas Físicas (sales + sale_items)
                $ventasQuery = DB::table('sale_items')
                    ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                    ->join('products_services', 'sale_items.product_service_id', '=', 'products_services.id')
                    ->where('sales.status', 'completada')
                    ->when($vendedor_id, fn($q) => $q->where('sales.user_id', $vendedor_id))
                    ->when($request->operador, fn($q) => $q->where('products_services.operador', $request->operador))
                    ->when($fecha_inicio && $fecha_fin, fn($q) => $q->whereBetween('sales.fecha_hora', [$fecha_inicio, $fecha_fin]))
                    ->select(
                        DB::raw("'Venta Física' as origen"),
                        'products_services.nombre as producto',
                        'products_services.operador',
                        DB::raw('MAX(products_services.precio_compra) as precio_compra_ref'),
                        DB::raw('SUM(sale_items.cantidad) as cantidad_total'),
                        DB::raw('SUM(sale_items.subtotal) as ingresos_totales'),
                        DB::raw('SUM((sale_items.precio_venta - COALESCE(NULLIF(sale_items.precio_compra, 0), products_services.precio_compra, 0)) * sale_items.cantidad) as ganancia_neta')
                    )
                    ->groupBy('products_services.nombre', 'products_services.operador');
                    
                // Filtro adicional para la parte 1 si se busca un producto/servicio específico
                if ($request->producto_id) {
                    $ventasQuery->where('sale_items.product_service_id', $request->producto_id);
                }

                // PARTE 2: Servicios (seller_movements)
                $serviciosQuery = DB::table('seller_movements')
                    ->join('cash_openings', 'seller_movements.cash_opening_id', '=', 'cash_openings.id')
                    // Join aproximado para obtener el nombre del producto si existe
                    ->leftJoin('products_services', function($join) {
                        $join->on('seller_movements.seccion', '=', 'products_services.seccion_reporte')
                             ->on('seller_movements.operador', '=', 'products_services.operador');
                    })
                    ->when($vendedor_id, fn($q) => $q->where('cash_openings.user_id', $vendedor_id))
                    ->when($request->operador, fn($q) => $q->where('seller_movements.operador', $request->operador))
                    ->when($fecha_inicio && $fecha_fin, fn($q) => $q->whereBetween('seller_movements.created_at', [$fecha_inicio, $fecha_fin]))
                    ->select(
                        DB::raw("'Servicio Digital' as origen"),
                        DB::raw("COALESCE(MAX(products_services.nombre), seller_movements.seccion) as producto"),
                        'seller_movements.operador',
                        DB::raw('0 as precio_compra_ref'),
                        DB::raw('SUM(COALESCE(seller_movements.cantidad, 1)) as cantidad_total'),
                        DB::raw('SUM(seller_movements.monto) as ingresos_totales'),
                        DB::raw('SUM(seller_movements.ganancia_generada) as ganancia_neta')
                    )
                    ->groupBy('seller_movements.seccion', 'seller_movements.operador');

                if ($request->producto_id) {
                    $serviciosQuery->where('products_services.id', $request->producto_id);
                }

                $queryFinal = $ventasQuery->unionAll($serviciosQuery);
                return DB::query()->fromSub($queryFinal, 'ganancias_unificadas')->orderBy('producto');

            case 'auditoria':
                return \App\Models\Log::with('user')
                    ->when($fecha_inicio && $fecha_fin, fn($q) => $q->whereBetween('created_at', [$fecha_inicio, $fecha_fin]))
                    ->when($request->usuario_id, fn($q) => $q->where('user_id', $request->usuario_id))
                    ->when($request->accion, fn($q) => $q->where('accion', $request->accion))
                    ->when($request->modelo, fn($q) => $q->where('modelo', $request->modelo))
                    ->orderBy('created_at', 'desc');

            case 'inventario':
                return \App\Models\ProductService::with('inventarios')
                    ->where('tipo', 'producto')
                    ->when($request->categoria, fn($q) => $q->where('categoria', $request->categoria))
                    ->when($request->operador, fn($q) => $q->where('operador', $request->operador))
                    ->orderBy('nombre', 'asc');

            default:
                return collect([]);
        }
    }

    private function generarHtmlTabla($tipo, $datos)
    {
        $html = '<style>
            table.report { width: 100%; border-collapse: collapse; font-family: helvetica; }
            table.report th { background-color: #e2e8f0; font-weight: bold; font-size: 9px; text-align: center; border: 1px solid #cccccc; padding: 5px; }
            table.report td { border: 1px solid #cccccc; font-size: 8px; padding: 4px; text-align: center; }
        </style>';
        
        $html .= '<table class="report" cellpadding="4">';
        
        switch ($tipo) {
            case 'usuarios':
                $html .= '<tr>
                            <th width="5%">Nº</th>
                            <th width="15%">CÓDIGO</th>
                            <th width="35%">NOMBRE COMPLETO</th>
                            <th width="20%">ROLES</th>
                            <th width="10%">ESTADO</th>
                            <th width="15%">CREACIÓN</th>
                          </tr>';
                $i = 0;
                foreach ($datos as $u) {
                    $fecha = Carbon::parse($u->created_at)->format('d/m/Y');
                    $roles = $u->roles->pluck('name')->implode(', ');
                    $bg = ($i % 2 == 0) ? '#ffffff' : '#f9f9f9';
                    $num = $i + 1;
                    $html .= "<tr style=\"background-color:{$bg};\">
                                <td>{$num}</td>
                                <td>{$u->codigo}</td>
                                <td style=\"text-align:left;\">{$u->nombre_completo}</td>
                                <td>" . strtoupper($roles) . "</td>
                                <td>" . strtoupper($u->estado) . "</td>
                                <td>{$fecha}</td>
                              </tr>";
                    $i++;
                }
                break;

            case 'productos':
                $html .= '<tr>
                            <th width="5%">Nº</th>
                            <th width="35%">NOMBRE</th>
                            <th width="12%">TIPO</th>
                            <th width="15%">SECCIÓN</th>
                            <th width="11%">OPERADOR</th>
                            <th width="10%">COMPRA</th>
                            <th width="12%">STOCK</th>
                          </tr>';
                $i = 0;
                foreach ($datos as $p) {
                    $stock = $p->tipo === 'producto' ? $p->stock_actual : 'N/A';
                    $seccion = $p->seccion_reporte ?: $p->categoria;
                    $bg = ($i % 2 == 0) ? '#ffffff' : '#f9f9f9';
                    $num = $i + 1;
                    $html .= "<tr style=\"background-color:{$bg};\">
                                <td>{$num}</td>
                                <td style=\"text-align:left;\">{$p->nombre}</td>
                                <td>" . strtoupper($p->tipo) . "</td>
                                <td>" . strtoupper($seccion) . "</td>
                                <td>{$p->operador}</td>
                                <td>" . number_format($p->precio_compra, 2) . "</td>
                                <td>{$stock}</td>
                              </tr>";
                    $i++;
                }
                break;

            case 'movimientos':
                $html .= '<tr>
                            <th width="5%">Nº</th>
                            <th width="12%">FECHA</th>
                            <th width="20%">VENDEDOR</th>
                            <th width="15%">SECCIÓN</th>
                            <th width="12%">OPERADOR</th>
                            <th width="12%">MONTO</th>
                            <th width="24%">OBSERVACIÓN</th>
                          </tr>';
                $i = 0;
                foreach ($datos as $m) {
                    $fecha = Carbon::parse($m->created_at)->format('d/m/Y H:i');
                    $vendedor = $m->aperturaCaja && $m->aperturaCaja->usuario ? $m->aperturaCaja->usuario->nombre_completo : 'N/A';
                    $operador = $m->operador ?: 'N/A';
                    $bg = ($i % 2 == 0) ? '#ffffff' : '#f9f9f9';
                    $num = $i + 1;
                    $html .= "<tr style=\"background-color:{$bg};\">
                                <td>{$num}</td>
                                <td>{$fecha}</td>
                                <td style=\"text-align:left;\">{$vendedor}</td>
                                <td>" . strtoupper(str_replace('_', ' ', $m->seccion)) . "</td>
                                <td>" . strtoupper($operador) . "</td>
                                <td>" . number_format($m->monto, 2) . "</td>
                                <td style=\"text-align:left;\">{$m->observacion}</td>
                              </tr>";
                    $i++;
                }
                break;

            case 'caja':
                $html .= '<tr>
                            <th width="5%">Nº</th>
                            <th width="15%">FECHA CIERRE</th>
                            <th width="20%">VENDEDOR</th>
                            <th width="12%">INICIAL</th>
                            <th width="12%">VENTAS</th>
                            <th width="12%">MOV. MANUALES</th>
                            <th width="12%">ESPERADO</th>
                            <th width="12%">ESTADO</th>
                          </tr>';
                $i = 0;
                foreach ($datos as $c) {
                    $fecha = Carbon::parse($c->fecha_hora_cierre)->format('d/m/Y H:i');
                    $vendedor = $c->aperturaCaja && $c->aperturaCaja->usuario ? $c->aperturaCaja->usuario->nombre_completo : 'N/A';
                    $inicial = $c->aperturaCaja ? number_format($c->aperturaCaja->saldo_inicial, 2) : '0.00';
                    $bg = ($i % 2 == 0) ? '#ffffff' : '#f9f9f9';
                    $num = $i + 1;
                    $html .= "<tr style=\"background-color:{$bg};\">
                                <td>{$num}</td>
                                <td>{$fecha}</td>
                                <td style=\"text-align:left;\">{$vendedor}</td>
                                <td>{$inicial}</td>
                                <td>" . number_format($c->total_ventas, 2) . "</td>
                                <td>" . number_format($c->total_movimientos, 2) . "</td>
                                <td>" . number_format($c->saldo_esperado, 2) . "</td>
                                <td>" . strtoupper($c->status) . "</td>
                              </tr>";
                    $i++;
                }
                break;

            case 'saldos':
                $html .= '<tr>
                            <th width="4%">Nº</th>
                            <th width="10%">SERVICIO</th>
                            <th width="10%">OPERADOR</th>
                            <th width="9%">LÍMITE</th>
                            <th width="9%">T.OPERADO</th>
                            <th width="9%">DISPONIBLE</th>
                            <th width="9%">INICIAL</th>
                            <th width="10%">FINAL(ESP)</th>
                            <th width="10%">ENTREGADO</th>
                            <th width="10%">SOBRANTE</th>
                            <th width="10%">FALTANTE</th>
                          </tr>';
                $i = 0;
                foreach ($datos as $s) {
                    $sobrante = $s->diferencia > 0 ? $s->diferencia : 0;
                    $faltante = $s->diferencia < 0 ? abs($s->diferencia) : 0;
                    $bg = ($i % 2 == 0) ? '#ffffff' : '#f9f9f9';
                    $num = $i + 1;
                    $html .= "<tr style=\"background-color:{$bg};\">
                                <td>{$num}</td>
                                <td>" . strtoupper($s->servicio) . "</td>
                                <td>" . strtoupper($s->operador) . "</td>
                                <td>" . number_format($s->limite_asignado, 2) . "</td>
                                <td>" . number_format($s->total_operado, 2) . "</td>
                                <td>" . number_format($s->disponible, 2) . "</td>
                                <td>" . number_format($s->saldo_inicial, 2) . "</td>
                                <td>" . number_format($s->saldo_esperado, 2) . "</td>
                                <td>" . number_format($s->entregado, 2) . "</td>
                                <td style=\"color:#16a34a;\">" . number_format($sobrante, 2) . "</td>
                                <td style=\"color:#dc2626;\">" . number_format($faltante, 2) . "</td>
                              </tr>";
                    $i++;
                }
                break;

            case 'ventas':
                $html .= '<tr>
                            <th width="4%">Nº</th>
                            <th width="12%">FECHA</th>
                            <th width="10%">CÓDIGO</th>
                            <th width="18%">VENDEDOR</th>
                            <th width="16%">CLIENTE</th>
                            <th width="10%">TIPO PAGO</th>
                            <th width="10%">ESTADO</th>
                            <th width="20%">DETALLE</th>
                          </tr>';
                $i = 0;
                foreach ($datos as $v) {
                    $fecha = Carbon::parse($v->fecha_hora)->format('d/m/Y H:i');
                    $detalles = '';
                    foreach ($v->items as $item) {
                        $detalles .= "{$item->cantidad}x {$item->productoServicio->nombre} | ";
                    }
                    $cliente = $v->cliente_nombre ?: 'S/N';
                    $bg = ($i % 2 == 0) ? '#ffffff' : '#f9f9f9';
                    $num = $i + 1;
                    $html .= "<tr style=\"background-color:{$bg};\">
                                <td>{$num}</td>
                                <td>{$fecha}</td>
                                <td>{$v->codigo}</td>
                                <td style=\"text-align:left;\">{$v->usuario->nombre_completo}</td>
                                <td style=\"text-align:left;\">{$cliente}</td>
                                <td>" . strtoupper($v->tipo_pago) . "</td>
                                <td>" . strtoupper($v->status) . "</td>
                                <td style=\"text-align:left;\">" . trim($detalles, ' | ') . " <br><strong>Tot: " . number_format($v->total, 2) . " Bs</strong></td>
                              </tr>";
                    $i++;
                }
                break;

            case 'ganancias':
                $html .= '<tr>
                            <th width="5%">Nº</th>
                            <th width="15%">ORIGEN</th>
                            <th width="23%">PRODUCTO / SERVICIO</th>
                            <th width="12%">OPERADOR</th>
                            <th width="10%">P.COMPRA</th>
                            <th width="10%">CANTIDAD</th>
                            <th width="10%">INGRESOS</th>
                            <th width="15%">GANANCIA NETA</th>
                          </tr>';
                $total_ganancia = 0;
                $total_ingresos = 0;
                $total_cantidad = 0;
                $i = 0;
                foreach ($datos as $g) {
                    $operador = $g->operador ?: '—';
                    $bg = ($i % 2 == 0) ? '#ffffff' : '#f9f9f9';
                    $num = $i + 1;
                    $html .= "<tr style=\"background-color:{$bg};\">
                                <td>{$num}</td>
                                <td>" . strtoupper($g->origen) . "</td>
                                <td style=\"text-align:left;\">{$g->producto}</td>
                                <td>{$operador}</td>
                                <td style=\"text-align:right;\">" . number_format($g->precio_compra_ref, 2) . "</td>
                                <td style=\"text-align:center;\">{$g->cantidad_total}</td>
                                <td style=\"text-align:right;\">" . number_format($g->ingresos_totales, 2) . "</td>
                                <td style=\"text-align:right; font-weight:bold; color:#16a34a;\">" . number_format($g->ganancia_neta, 2) . "</td>
                              </tr>";
                    $total_ganancia += $g->ganancia_neta;
                    $total_ingresos += $g->ingresos_totales;
                    $total_cantidad += $g->cantidad_total;
                    $i++;
                }
                $html .= '</table>';
                // Resumen de totales separado de la tabla
                $html .= '<div style="margin-top:10px; padding:8px 12px; background-color:#e2e8f0; border:1px solid #ccc; font-family:helvetica; font-size:9px;">
                    <table width="100%" cellpadding="2" style="border:none;">
                        <tr>
                            <td style="border:none; font-weight:bold; text-align:left; width:33%;">TOTAL CANTIDAD VENDIDA: ' . $total_cantidad . '</td>
                            <td style="border:none; font-weight:bold; text-align:center; width:33%;">TOTAL INGRESOS: ' . number_format($total_ingresos, 2) . ' Bs</td>
                            <td style="border:none; font-weight:bold; text-align:right; width:33%; color:#16a34a;">GANANCIA NETA TOTAL: ' . number_format($total_ganancia, 2) . ' Bs</td>
                        </tr>
                    </table>
                </div>';
                $html .= '<p style="font-size:7px; color:#666; margin-top:8px; font-style:italic;">* Las ganancias de servicios se calculan según comisión/margen configurado. Las ventas físicas usan costo histórico al momento de la venta.</p>';
                $html .= '<table class="report" cellpadding="4">';
                break;

            case 'auditoria':
                $html .= '<tr>
                            <th width="5%">Nº</th>
                            <th width="15%">FECHA Y HORA</th>
                            <th width="20%">USUARIO</th>
                            <th width="15%">ACCIÓN</th>
                            <th width="15%">MÓDULO AFECTADO</th>
                            <th width="15%">ID REGISTRO</th>
                            <th width="15%">IP ADDRESS</th>
                          </tr>';
                $i = 0;
                foreach ($datos as $log) {
                    $fecha = Carbon::parse($log->created_at)->format('d/m/Y H:i:s');
                    $usuario = $log->user ? $log->user->nombre_completo : 'SISTEMA';
                    $bg = ($i % 2 == 0) ? '#ffffff' : '#f9f9f9';
                    $num = $i + 1;
                    $html .= "<tr style=\"background-color:{$bg};\">
                                <td>{$num}</td>
                                <td>{$fecha}</td>
                                <td style=\"text-align:left;\">{$usuario}</td>
                                <td>" . strtoupper($log->accion) . "</td>
                                <td>" . class_basename($log->modelo) . "</td>
                                <td>{$log->modelo_id}</td>
                                <td>{$log->ip_address}</td>
                              </tr>";
                    $i++;
                }
                break;

            case 'inventario':
                $html .= '<tr>
                            <th width="5%">Nº</th>
                            <th width="35%">PRODUCTO/SERVICIO</th>
                            <th width="15%">TIPO</th>
                            <th width="15%">OPERADOR</th>
                            <th width="15%">COMPRA</th>
                            <th width="15%">STOCK ACTUAL</th>
                          </tr>';
                $i = 0;
                foreach ($datos as $inv) {
                    $stock = $inv->tipo === 'producto' ? $inv->stock_actual : 'N/A';
                    $bg = ($i % 2 == 0) ? '#ffffff' : '#f9f9f9';
                    $num = $i + 1;
                    $html .= "<tr style=\"background-color:{$bg};\">
                                <td>{$num}</td>
                                <td style=\"text-align:left;\">{$inv->nombre}</td>
                                <td>" . strtoupper($inv->tipo) . "</td>
                                <td>{$inv->operador}</td>
                                <td>" . number_format($inv->precio_compra, 2) . "</td>
                                <td>{$stock}</td>
                              </tr>";
                    $i++;
                }
                break;
        }
        
        $html .= '</table>';
        return $html;
    }
}
