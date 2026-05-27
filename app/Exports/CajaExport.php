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

class CajaExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    use ConCabeceraReporte;

    protected $request;
    protected string $tituloReporte = 'APERTURA Y CIERRE DE CAJA';
    protected int $totalColumnas = 11;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $controller = new ReporteController();
        return $controller->construirQuery('caja', $this->request)->get();
    }

    public function headings(): array
    {
        return [
            'ID CIERRE',
            'FECHA DE CIERRE',
            'VENDEDOR',
            'SALDO INICIAL (Bs)',
            'TOTAL VENTAS (Bs)',
            'TOTAL MOVIMIENTOS (Bs)',
            'SALDO ESPERADO (Bs)',
            'SALDO ENTREGADO (Bs)',
            'DIFERENCIA (Bs)',
            'ESTADO',
            'OBSERVACIÓN'
        ];
    }

    public function map($cierre): array
    {
        $vendedor = $cierre->aperturaCaja && $cierre->aperturaCaja->usuario 
            ? $cierre->aperturaCaja->usuario->nombre_completo 
            : 'Desconocido';

        return [
            $cierre->id,
            Carbon::parse($cierre->fecha_hora_cierre)->format('d/m/Y H:i'),
            $vendedor,
            number_format($cierre->aperturaCaja ? $cierre->aperturaCaja->saldo_inicial : 0, 2),
            number_format($cierre->total_ventas, 2),
            number_format($cierre->total_movimientos, 2),
            number_format($cierre->saldo_esperado, 2),
            number_format($cierre->saldo_entregado, 2),
            number_format($cierre->diferencia, 2),
            strtoupper($cierre->status),
            $cierre->observacion ?? 'Sin observación'
        ];
    }

    public function registerEvents(): array
    {
        return $this->registrarCabecera();
    }
}
