<?php

namespace App\Exports;

use App\Http\Controllers\ReporteController;
use App\Exports\Concerns\ConCabeceraReporte;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Carbon\Carbon;
use Illuminate\Http\Request;

class VentasExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    use ConCabeceraReporte;

    protected $request;
    protected string $tituloReporte = 'REPORTE DE VENTAS';
    protected int $totalColumnas = 9;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $controller = new ReporteController();
        return $controller->construirQuery('ventas', $this->request)->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'CÓDIGO',
            'FECHA Y HORA',
            'VENDEDOR',
            'CLIENTE',
            'TIPO DE PAGO',
            'ESTADO',
            'TOTAL (Bs)',
            'DETALLES DE PRODUCTOS'
        ];
    }

    public function map($venta): array
    {
        $detalles = '';
        foreach ($venta->items as $item) {
            $nombre = $item->productoServicio ? $item->productoServicio->nombre : 'Desconocido';
            $detalles .= "{$item->cantidad}x {$nombre} (Bs {$item->subtotal}) | ";
        }

        return [
            $venta->id,
            $venta->codigo,
            Carbon::parse($venta->fecha_hora)->format('d/m/Y H:i'),
            $venta->usuario ? $venta->usuario->nombre_completo : 'N/A',
            $venta->cliente_nombre ?: 'S/N',
            strtoupper($venta->tipo_pago),
            strtoupper($venta->status),
            number_format($venta->total, 2),
            trim($detalles, ' | ')
        ];
    }

    public function registerEvents(): array
    {
        return $this->registrarCabecera();
    }
}
