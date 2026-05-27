<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ProductService;
use App\Models\Inventory;
use App\Models\CashOpening;
use App\Models\CashClosure;
use App\Http\Requests\StoreVentaRequest;
use App\Http\Requests\AnularVentaRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VentaController extends Controller
{
    /**
     * Listar ventas con filtros.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->input('search');
        $estado = $request->input('estado');
        $tipo_pago = $request->input('tipo_pago');
        $fecha = $request->input('fecha');

        $query = Sale::with(['usuario', 'items.productoServicio'])->withTrashed();

        // Si es vendedor, solo ve sus propias ventas
        if ($user->hasRole('vendedor')) {
            $query->where('user_id', $user->id);
        }

        if ($search) {
            $query->where('codigo', 'like', "%{$search}%");
        }

        if ($estado) {
            $query->where('status', $estado);
        }

        if ($tipo_pago) {
            $query->where('tipo_pago', $tipo_pago);
        }

        if ($fecha) {
            $query->whereDate('fecha_hora', $fecha);
        }

        $ventas = $query->orderBy('fecha_hora', 'desc')->paginate(15)->withQueryString();

        return Inertia::render('Ventas/Index', [
            'ventas' => $ventas,
            'filtros' => [
                'search' => $search,
                'estado' => $estado,
                'tipo_pago' => $tipo_pago,
                'fecha' => $fecha,
            ]
        ]);
    }

    /**
     * Mostrar formulario de creación de venta (carrito).
     */
    public function create()
    {
        $user = Auth::user();

        // Buscar la apertura activa del vendedor
        $apertura = CashOpening::where('user_id', $user->id)
            ->where('status', 'abierta')
            ->first();

        // Si es admin, buscar cualquier apertura abierta o la propia
        if (!$apertura && $user->hasRole('administrador')) {
            $apertura = CashOpening::where('status', 'abierta')->first();
        }

        // Verificar bloqueo por cierre
        $cierreAprobado = false;
        if ($apertura) {
            $cierreAprobado = CashClosure::where('cash_opening_id', $apertura->id)
                ->where('status', 'aprobado')
                ->exists();
        }

        // Productos activos disponibles para la venta
        $productos = ProductService::activos()->orderBy('nombre')->get();

        return Inertia::render('Ventas/Create', [
            'apertura_activa' => $apertura,
            'cierre_aprobado' => $cierreAprobado,
            'productos' => $productos,
        ]);
    }

    /**
     * Registrar una nueva venta con múltiples ítems (carrito).
     */
    public function store(StoreVentaRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            // Generar código secuencial: V-000001
            $ultimoCodigo = Sale::withTrashed()->orderBy('id', 'desc')->value('codigo');
            $siguienteNumero = 1;
            if ($ultimoCodigo) {
                $partes = explode('-', $ultimoCodigo);
                $siguienteNumero = intval(end($partes)) + 1;
            }
            $codigo = 'V-' . str_pad($siguienteNumero, 6, '0', STR_PAD_LEFT);

            $apertura = CashOpening::findOrFail($validated['cash_opening_id']);

            // Calcular total general
            $totalGeneral = 0;
            foreach ($validated['items'] as $item) {
                $totalGeneral += $item['cantidad'] * $item['precio_venta'];
            }

            // Crear la venta
            $venta = Sale::create([
                'codigo' => $codigo,
                'user_id' => $apertura->user_id,
                'cash_opening_id' => $apertura->id,
                'fecha_hora' => Carbon::now(),
                'total' => $totalGeneral,
                'tipo_pago' => $validated['tipo_pago'],
                'cliente_nombre' => $validated['cliente_nombre'] ?? null,
                'observacion' => $validated['observacion'] ?? null,
                'status' => 'completada',
            ]);

            // Procesar cada ítem del carrito
            foreach ($validated['items'] as $itemData) {
                $producto = ProductService::findOrFail($itemData['product_service_id']);
                $subtotal = $itemData['cantidad'] * $itemData['precio_venta'];

                // Verificar stock si es producto físico
                if ($producto->tipo === 'producto') {
                    if ($producto->stock_actual < $itemData['cantidad']) {
                        throw new \Exception("Stock insuficiente para '{$producto->nombre}'. Disponible: {$producto->stock_actual}, Solicitado: {$itemData['cantidad']}.");
                    }

                    // Descontar stock
                    $producto->decrement('stock_actual', $itemData['cantidad']);

                    // Registrar movimiento de inventario (salida por venta)
                    Inventory::create([
                        'product_service_id' => $producto->id,
                        'tipo_inventario' => 'fisico',
                        'cantidad_ingreso' => $itemData['cantidad'],
                        'stock_actual' => $producto->fresh()->stock_actual,
                        'stock_previsto' => $producto->fresh()->stock_actual,
                        'precio_compra' => $producto->precio_compra,
                        'fecha_hora' => Carbon::now(),
                        'tipo_movimiento' => 'egreso',
                        'observacion' => 'Descuento por venta ' . $codigo,
                    ]);
                }

                // Crear el ítem de venta
                SaleItem::create([
                    'sale_id' => $venta->id,
                    'product_service_id' => $producto->id,
                    'cantidad' => $itemData['cantidad'],
                    'precio_venta' => $itemData['precio_venta'],
                    'precio_compra' => $producto->precio_compra,
                    'subtotal' => $subtotal,
                ]);
            }

            DB::commit();

            return redirect()->route('ventas.show', $venta->id)
                ->with('exito', 'Venta registrada exitosamente. Código: ' . $codigo);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Mostrar detalle de una venta específica.
     */
    public function show(Sale $venta)
    {
        $venta->load(['usuario', 'items.productoServicio', 'aperturaCaja']);

        return Inertia::render('Ventas/Show', [
            'venta' => $venta,
        ]);
    }

    /**
     * Anular una venta: soft delete + reversión de stock + motivo.
     */
    public function anular(AnularVentaRequest $request, Sale $venta)
    {
        // Verificar que no esté ya anulada
        if ($venta->status === 'anulada') {
            return redirect()->back()->with('error', 'Esta venta ya fue anulada previamente.');
        }

        // Verificar que no exista cierre de caja aprobado
        $cierreAprobado = CashClosure::where('cash_opening_id', $venta->cash_opening_id)
            ->where('status', 'aprobado')
            ->exists();

        if ($cierreAprobado) {
            return redirect()->back()->with('error', 'No se puede anular: la caja tiene un cierre aprobado.');
        }

        DB::beginTransaction();
        try {
            $validated = $request->validated();

            // Marcar como anulada
            $venta->status = 'anulada';
            $venta->motivo_anulacion = $validated['motivo_anulacion'];
            $venta->save();

            // Revertir stock de cada ítem
            foreach ($venta->items as $item) {
                $producto = $item->productoServicio;

                if ($producto && $producto->tipo === 'producto') {
                    $producto->increment('stock_actual', $item->cantidad);

                    // Registrar movimiento de inventario (ajuste por anulación)
                    Inventory::create([
                        'product_service_id' => $producto->id,
                        'tipo_inventario' => 'fisico',
                        'cantidad_ingreso' => $item->cantidad,
                        'stock_actual' => $producto->fresh()->stock_actual,
                        'stock_previsto' => $producto->fresh()->stock_actual,
                        'precio_compra' => $producto->precio_compra,
                        'fecha_hora' => Carbon::now(),
                        'tipo_movimiento' => 'ajuste',
                        'observacion' => 'Reversión por anulación venta ' . $venta->codigo,
                    ]);
                }
            }

            // Soft delete de la venta
            $venta->delete();

            DB::commit();

            return redirect()->route('ventas.index')
                ->with('exito', 'Venta anulada correctamente. El stock ha sido revertido.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al anular la venta: ' . $e->getMessage());
        }
    }

    public function imprimir(Sale $venta)
    {
        $venta->load(['usuario', 'items.productoServicio', 'aperturaCaja']);
        $config = \App\Models\SystemConfig::first();

        $formato = $config->formato_impresion ?? 'termico';

        if ($formato === 'estandar_carta') {
            return $this->imprimirCarta($venta, $config);
        } else {
            return $this->imprimirTermico($venta, $config);
        }
    }

    private function imprimirCarta(Sale $venta, $config)
    {
        $pdf = new \App\Services\PdfReportService('P', 'NOTA DE ENTREGA');

        $html = '<style>
            table.report { width: 100%; border-collapse: collapse; font-family: helvetica; }
            table.report th { background-color: #e2e8f0; font-weight: bold; font-size: 10px; border: 1px solid #cccccc; padding: 5px; text-align: left; }
            table.report td { border: 1px solid #cccccc; font-size: 9px; padding: 4px; }
            table.report td.right { text-align: right; }
            h3 { font-family: helvetica; font-size: 12px; margin-bottom: 5px; }
            p { font-family: helvetica; font-size: 10px; margin: 2px 0; }
        </style>';

        $html .= '<p><strong>CÓDIGO DE VENTA:</strong> ' . $venta->codigo . '</p>';
        $html .= '<p><strong>FECHA:</strong> ' . \Carbon\Carbon::parse($venta->fecha_hora)->format('d/m/Y H:i') . '</p>';
        $html .= '<p><strong>VENDEDOR:</strong> ' . $venta->usuario->nombre_completo . '</p>';
        if ($venta->cliente_nombre) {
            $html .= '<p><strong>CLIENTE:</strong> ' . $venta->cliente_nombre . '</p>';
        }
        $html .= '<p><strong>MÉTODO DE PAGO:</strong> ' . mb_strtoupper($venta->tipo_pago) . '</p>';
        $html .= '<br><br>';

        $html .= '<h3>DETALLE DE PRODUCTOS/SERVICIOS</h3>';
        $html .= '<table class="report" cellpadding="4">';
        $html .= '<tr><th width="50%">DESCRIPCIÓN</th><th width="15%" style="text-align:center;">CANT.</th><th width="15%" style="text-align:right;">P. UNITARIO</th><th width="20%" style="text-align:right;">SUBTOTAL (BS)</th></tr>';

        $i = 0;
        foreach ($venta->items as $item) {
            $nombre = $item->productoServicio->nombre;
            $bg = ($i++ % 2 == 0) ? '#ffffff' : '#f9f9f9';
            $html .= '<tr style="background-color:'.$bg.';">';
            $html .= '<td width="50%">' . $nombre . '</td>';
            $html .= '<td width="15%" style="text-align:center;">' . $item->cantidad . '</td>';
            $html .= '<td width="15%" class="right">' . number_format($item->precio_venta, 2) . '</td>';
            $html .= '<td width="20%" class="right">' . number_format($item->subtotal, 2) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '<tr style="background-color:#e2e8f0; font-weight:bold;">';
        $html .= '<td colspan="3" class="right">TOTAL A PAGAR:</td>';
        $html .= '<td class="right">' . number_format($venta->total, 2) . '</td>';
        $html .= '</tr>';
        $html .= '</table><br>';

        if ($venta->observacion) {
            $html .= '<p><strong>OBSERVACIONES:</strong> ' . $venta->observacion . '</p>';
        }

        $html .= '<br><br><br>';
        $html .= '<div style="text-align:center;">';
        $html .= '<p><em>¡Gracias por su preferencia!</em></p>';
        $html .= '<p>Conserve esta nota para cualquier reclamo.</p>';
        $html .= '</div>';

        $pdf->renderHtml($html);

        $pdfContent = $pdf->Output('nota_entrega_'.$venta->codigo.'.pdf', 'S');

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="nota_entrega_'.$venta->codigo.'.pdf"');
    }

    private function imprimirTermico(Sale $venta, $config)
    {
        $anchoStr = $config->tamano_papel_thermal ?? '58mm';
        $ancho = ($anchoStr === '80mm') ? 80 : 58;

        $pdf = new \TCPDF('P', 'mm', array($ancho, 200), true, 'UTF-8', false);

        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor($config->nombre_sistema ?? 'Sistema de Ventas');
        $pdf->SetTitle('Nota de Entrega - ' . $venta->codigo);

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        $pdf->SetMargins(2, 5, 2);
        $pdf->SetAutoPageBreak(TRUE, 5);

        $pdf->AddPage();

        $pdf->SetFont('helvetica', '', 8);

        $html = '<div style="text-align:center;">';
        $html .= '<strong>' . mb_strtoupper($config->nombre_sistema ?? 'SISTEMA DE VENTAS') . '</strong><br>';
        $html .= 'Actividad: ' . ($config->actividad ?? 'Ventas') . '<br>';
        $html .= '--------------------------------------<br>';
        $html .= '<strong>NOTA DE ENTREGA</strong><br>';
        $html .= '</div>';

        $html .= '<strong>CÓDIGO:</strong> ' . $venta->codigo . '<br>';
        $html .= '<strong>FECHA:</strong> ' . \Carbon\Carbon::parse($venta->fecha_hora)->format('d/m/Y H:i') . '<br>';
        $html .= '<strong>VENDEDOR:</strong> ' . $venta->usuario->nombre_completo . '<br>';
        if ($venta->cliente_nombre) {
            $html .= '<strong>CLIENTE:</strong> ' . $venta->cliente_nombre . '<br>';
        }
        $html .= '<div style="text-align:center;">--------------------------------------</div>';

        $html .= '<table style="width:100%;" cellpadding="1">';
        $html .= '<tr>
                    <td width="55%"><strong>DESCRIPCIÓN</strong></td>
                    <td width="15%" align="center"><strong>CANT</strong></td>
                    <td width="30%" align="right"><strong>SUBTOT</strong></td>
                  </tr>';

        foreach ($venta->items as $item) {
            $nombre = $item->productoServicio->nombre;
            if (mb_strlen($nombre) > 20) {
                $nombre = mb_substr($nombre, 0, 18) . '..';
            }
            $html .= '<tr>';
            $html .= '<td width="55%">' . $nombre . '</td>';
            $html .= '<td width="15%" align="center">' . $item->cantidad . '</td>';
            $html .= '<td width="30%" align="right">' . number_format($item->subtotal, 2) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';

        $html .= '<div style="text-align:center;">--------------------------------------</div>';
        $html .= '<table style="width:100%;">';
        $html .= '<tr><td width="50%"><strong>TOTAL:</strong></td><td width="50%" align="right"><strong>' . number_format($venta->total, 2) . ' Bs</strong></td></tr>';
        $html .= '<tr><td width="50%">PAGO:</td><td width="50%" align="right">' . mb_strtoupper($venta->tipo_pago) . '</td></tr>';
        $html .= '</table>';

        if ($venta->observacion) {
            $html .= '<br><strong>NOTA:</strong> ' . $venta->observacion . '<br>';
        }

        $html .= '<br><div style="text-align:center;">';
        $html .= '<em>¡Gracias por su preferencia!</em><br>';
        $html .= 'Conserve esta nota para<br>cualquier reclamo.';
        $html .= '</div>';

        $pdf->writeHTML($html, true, false, true, false, '');

        $pdfContent = $pdf->Output('nota_entrega_'.$venta->codigo.'.pdf', 'S');

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="nota_entrega_'.$venta->codigo.'.pdf"');
    }
}
