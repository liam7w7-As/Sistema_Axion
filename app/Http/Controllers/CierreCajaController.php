<?php

namespace App\Http\Controllers;

use App\Models\CashClosure;
use App\Models\CashOpening;
use App\Models\Sale;
use App\Models\SellerMovement;
use App\Models\SystemConfig;
use App\Http\Requests\StoreCierreCajaRequest;
use App\Http\Requests\ApproveCierreCajaRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use TCPDF;

class CierreCajaController extends Controller
{
    /**
     * Obtener resumen de secciones unificando Movimientos y Ventas
     */
    private function getResumenSecciones($cash_opening_id)
    {
        $secciones = [
            'tarjetas_unidad' => 0, 'tarjetas_mayor' => 0, 'recuperaciones' => 0,
            'chips' => 0, 'recargas' => 0, 'megas' => 0, 'servicios_digitales' => 0,
            'banca_digital' => 0, 'servicio_tecnico' => 0, 'efectivo_monedas' => 0,
        ];

        // 1. Movimientos manuales
        $movimientos = SellerMovement::where('cash_opening_id', $cash_opening_id)->get();
        foreach ($movimientos as $mov) {
            $seccion = $mov->seccion;
            if (array_key_exists($seccion, $secciones)) {
                $secciones[$seccion] += $mov->monto;
            }
        }

        // 2. Ventas del carrito
        $ventas = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products_services', 'sale_items.product_service_id', '=', 'products_services.id')
            ->where('sales.cash_opening_id', $cash_opening_id)
            ->where('sales.status', 'completada')
            ->select(
                'products_services.seccion_reporte as seccion',
                DB::raw('SUM(sale_items.subtotal) as total_monto')
            )
            ->groupBy('products_services.seccion_reporte')
            ->get();

        foreach ($ventas as $v) {
            if ($v->seccion && array_key_exists($v->seccion, $secciones)) {
                $secciones[$v->seccion] += $v->total_monto;
            }
        }

        return $secciones;
    }
    /**
     * Listar los cierres de caja.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->input('search');
        $estado = $request->input('estado');
        $vendedor_id = $request->input('vendedor_id');

        $query = CashClosure::with(['aperturaCaja.usuario', 'aprobadoPor'])->orderBy('id', 'desc');

        if ($user->hasRole('vendedor')) {
            $query->whereHas('aperturaCaja', function($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } elseif ($vendedor_id) {
            $query->whereHas('aperturaCaja', function($q) use ($vendedor_id) {
                $q->where('user_id', $vendedor_id);
            });
        }

        if ($estado) {
            $query->where('status', $estado);
        }

        $cierres = $query->paginate(15)->withQueryString();

        // Para el filtro de vendedores en Admin
        $vendedores = [];
        $vendedoresConCajaAbierta = [];
        if ($user->hasRole('administrador')) {
            $vendedores = \App\Models\User::role('vendedor')->get(['id', 'nombre_completo']);

            // Obtener vendedores con caja abierta para las cards
            $vendedoresActivos = \App\Models\User::role('vendedor')->habilitados()
                ->whereHas('aperturasCaja', function ($q) {
                    $q->where('status', 'abierta');
                })
                ->with(['aperturasCaja' => function ($q) {
                    $q->where('status', 'abierta')->latest('id');
                }])
                ->get();

            foreach ($vendedoresActivos as $vend) {
                $apertura = $vend->aperturasCaja->first();
                if ($apertura) {
                    $total_ventas = Sale::where('cash_opening_id', $apertura->id)
                        ->where('status', 'completada')
                        ->sum('total');
                    $total_movimientos = DB::table('seller_movements')
                        ->where('cash_opening_id', $apertura->id)
                        ->sum('monto');
                    
                    $vend->saldo_esperado = $apertura->saldo_inicial + $total_ventas + $total_movimientos;
                    $vend->total_ventas = $total_ventas;
                    $vendedoresConCajaAbierta[] = $vend;
                }
            }
        }

        return Inertia::render('CierresCaja/Index', [
            'cierres' => $cierres,
            'vendedores' => $vendedores,
            'vendedoresConCajaAbierta' => $vendedoresConCajaAbierta,
            'filtros' => [
                'search' => $search,
                'estado' => $estado,
                'vendedor_id' => $vendedor_id,
            ]
        ]);
    }

    /**
     * Mostrar formulario para registrar el cierre de caja de la jornada actual.
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        $vendedor_id = $request->input('vendedor_id');

        // Determinar qué usuario estamos intentando cerrar
        $target_user_id = $user->id;
        if ($user->hasRole('administrador') && $vendedor_id) {
            $target_user_id = $vendedor_id;
        }

        // Buscar la apertura abierta del usuario objetivo
        $apertura = CashOpening::with('usuario')
            ->where('user_id', $target_user_id)
            ->where('status', 'abierta')
            ->first();

        if (!$apertura && $user->hasRole('administrador') && !$vendedor_id) {
            // Si el admin no especifica vendedor y no tiene caja, buscar la primera abierta general (comportamiento legacy)
            $apertura = CashOpening::with('usuario')->where('status', 'abierta')->first();
        }

        if (!$apertura) {
            return Inertia::render('CierresCaja/Create', [
                'error_mensaje' => 'No tiene una apertura de caja activa para cerrar.'
            ]);
        }

        // Ya existe cierre?
        $cierrePrevio = CashClosure::where('cash_opening_id', $apertura->id)->first();
        if ($cierrePrevio) {
            return Inertia::render('CierresCaja/Create', [
                'error_mensaje' => 'Esta jornada ya tiene un cierre registrado (Estado: ' . strtoupper($cierrePrevio->status) . ').'
            ]);
        }

        // Calcular Totales
        $total_ventas = Sale::where('cash_opening_id', $apertura->id)
            ->where('status', 'completada')
            ->sum('total');

        // Resumen por secciones consolidado
        $secciones = $this->getResumenSecciones($apertura->id);

        $total_movimientos = DB::table('seller_movements')
            ->where('cash_opening_id', $apertura->id)
            ->sum('monto');

        $saldo_esperado = $apertura->saldo_inicial + $total_ventas + $total_movimientos;

        return Inertia::render('CierresCaja/Create', [
            'apertura' => $apertura,
            'total_ventas' => $total_ventas,
            'total_movimientos' => $total_movimientos,
            'saldo_esperado' => $saldo_esperado,
            'resumen_secciones' => $secciones,
        ]);
    }

    /**
     * Registrar el cierre de caja.
     */
    public function store(StoreCierreCajaRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $apertura = CashOpening::findOrFail($validated['cash_opening_id']);

            $total_ventas = Sale::where('cash_opening_id', $apertura->id)
                ->where('status', 'completada')
                ->sum('total');

            $total_movimientos = DB::table('seller_movements')
                ->where('cash_opening_id', $apertura->id)
                ->sum('monto');

            $saldo_esperado = $apertura->saldo_inicial + $total_ventas + $total_movimientos;
            $saldo_entregado = $validated['saldo_entregado'];

            $diferencia = $saldo_entregado - $saldo_esperado;
            $sobrante = $diferencia > 0 ? $diferencia : 0;
            $faltante = $diferencia < 0 ? abs($diferencia) : 0;

            $cierre = CashClosure::create([
                'cash_opening_id' => $apertura->id,
                'fecha_hora_cierre' => Carbon::now(),
                'saldo_inicial' => $apertura->saldo_inicial,
                'total_ventas' => $total_ventas,
                'total_movimientos' => $total_movimientos,
                'saldo_esperado' => $saldo_esperado,
                'saldo_entregado' => $saldo_entregado,
                'sobrante' => $sobrante,
                'faltante' => $faltante,
                'observacion' => $validated['observacion'] ?? null,
                'status' => 'pendiente',
            ]);

            DB::commit();

            return redirect()->route('cierres-caja.show', $cierre->id)
                ->with('exito', 'Cierre de caja registrado exitosamente. Pendiente de aprobación.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al registrar el cierre: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar detalle del cierre.
     */
    public function show($id)
    {
        $cierre = CashClosure::with(['aperturaCaja.usuario', 'aprobadoPor'])->findOrFail($id);
        
        // Calcular resumen para mostrar
        $secciones = $this->getResumenSecciones($cierre->cash_opening_id);

        return Inertia::render('CierresCaja/Show', [
            'cierre' => $cierre,
            'resumen_secciones' => $secciones,
        ]);
    }

    /**
     * Aprobar el cierre de caja.
     */
    public function aprobar(ApproveCierreCajaRequest $request, $id)
    {
        $cierre = CashClosure::findOrFail($id);

        if ($cierre->status !== 'pendiente') {
            return redirect()->back()->with('error', 'El cierre ya no está pendiente de aprobación.');
        }

        DB::beginTransaction();
        try {
            $validated = $request->validated();

            $cierre->status = 'aprobado';
            $cierre->approved_by = Auth::id();
            if (isset($validated['observacion_aprobacion'])) {
                $cierre->observacion .= "\n\nAprobación: " . $validated['observacion_aprobacion'];
            }
            $cierre->save();

            // Bloqueo definitivo: cerrar la apertura
            $apertura = $cierre->aperturaCaja;
            $apertura->status = 'cerrada';
            $apertura->save();

            DB::commit();

            return redirect()->route('cierres-caja.index')
                ->with('exito', 'Cierre de caja aprobado exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al aprobar el cierre: ' . $e->getMessage());
        }
    }

    /**
     * Imprimir Reporte en 58mm (ERS 2.3.5)
     */
    public function imprimir($id)
    {
        $cierre = CashClosure::with(['aperturaCaja.usuario', 'aprobadoPor'])->findOrFail($id);
        
        $pdf = new \App\Services\PdfReportService('P', 'REPORTE DE CIERRE DE CAJA.[WEB]');

        $html = '<style>
            table.report { width: 100%; border-collapse: collapse; font-family: helvetica; }
            table.report th { background-color: #e2e8f0; font-weight: bold; font-size: 10px; border: 1px solid #cccccc; padding: 5px; text-align: left; }
            table.report td { border: 1px solid #cccccc; font-size: 9px; padding: 4px; }
            table.report td.right { text-align: right; }
            h3 { font-family: helvetica; font-size: 12px; margin-bottom: 5px; }
            p { font-family: helvetica; font-size: 10px; margin: 2px 0; }
        </style>';

        $html .= '<table style="width: 100%; border-collapse: collapse; font-family: helvetica; font-size: 10px;" cellpadding="2">';
        $html .= '<tr>';
        $html .= '<td width="33%"><strong>APERTURA N°:</strong> ' . $cierre->cash_opening_id . '</td>';
        $html .= '<td width="33%"><strong>VENDEDOR:</strong> ' . $cierre->aperturaCaja->usuario->nombre_completo . '</td>';
        $html .= '<td width="34%"><strong>FECHA DE CIERRE:</strong> ' . \Carbon\Carbon::parse($cierre->fecha_hora_cierre)->format('d/m/Y H:i') . '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td width="33%"><strong>ESTADO:</strong> ' . mb_strtoupper($cierre->status) . '</td>';
        $html .= '<td width="67%" colspan="2"><strong>APROBADO POR:</strong> ' . ($cierre->aprobadoPor ? $cierre->aprobadoPor->nombre_completo : 'N/A') . '</td>';
        $html .= '</tr>';
        $html .= '</table>';
        $html .= '<br>';

        $html .= '<h3>RESUMEN POR SECCIONES</h3>';
        
        // Resumen
        $secciones = $this->getResumenSecciones($cierre->cash_opening_id);

        $html .= '<table class="report" cellpadding="4">';
        $html .= '<tr><th width="60%">SECCIÓN CONTABLE</th><th width="40%" style="text-align:right;">MONTO INGRESADO (BS)</th></tr>';
        
        $i = 0;
        foreach ($secciones as $clave => $monto) {
            $nombre = mb_strtoupper(str_replace('_', ' ', $clave));
            $bg = ($i++ % 2 == 0) ? '#ffffff' : '#f9f9f9';
            $html .= '<tr style="background-color:'.$bg.';"><td width="60%">' . $nombre . '</td><td width="40%" class="right">' . number_format($monto, 2) . '</td></tr>';
        }
        $html .= '</table><br><br>';

        // Totales
        $html .= '<h3>TOTALES DE CAJA</h3>';
        $html .= '<table class="report" cellpadding="4">';
        $html .= '<tr style="background-color:#ffffff;"><td width="60%">FONDO DE CAMBIO</td><td width="40%" class="right">' . number_format($cierre->saldo_inicial, 2) . '</td></tr>';
        $html .= '<tr style="background-color:#f9f9f9;"><td width="60%">TOTAL VENTAS</td><td width="40%" class="right">' . number_format($cierre->total_ventas, 2) . '</td></tr>';
        $html .= '<tr style="background-color:#ffffff;"><td width="60%">TOTAL MOVIMIENTOS MANUALES</td><td width="40%" class="right">' . number_format($cierre->total_movimientos, 2) . '</td></tr>';
        $html .= '<tr style="background-color:#e2e8f0; font-weight:bold;"><td width="60%">SALDO ESPERADO EN CAJA</td><td width="40%" class="right">' . number_format($cierre->saldo_esperado, 2) . '</td></tr>';
        $html .= '<tr style="background-color:#ffffff;"><td width="60%">SALDO DECLARADO POR VENDEDOR</td><td width="40%" class="right">' . number_format($cierre->saldo_entregado, 2) . '</td></tr>';
        $html .= '</table><br><br>';

        // Diferencias
        $html .= '<h3>DIFERENCIA DE CIERRE</h3>';
        $html .= '<table class="report" cellpadding="4">';
        $html .= '<tr style="background-color:#ffffff;"><td width="60%">SOBRANTE</td><td width="40%" class="right" style="color:green; font-weight:bold;">' . number_format($cierre->sobrante, 2) . '</td></tr>';
        $html .= '<tr style="background-color:#f9f9f9;"><td width="60%">FALTANTE</td><td width="40%" class="right" style="color:red; font-weight:bold;">' . number_format($cierre->faltante, 2) . '</td></tr>';
        $html .= '</table>';
        


        $pdf->renderHtml($html);

        $pdfContent = $pdf->Output('reporte_cierre_'.$cierre->cash_opening_id.'.pdf', 'S');

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="reporte_cierre_'.$cierre->cash_opening_id.'.pdf"');
    }
}
