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

class MovimientosExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    use ConCabeceraReporte;

    protected $request;
    protected string $tituloReporte = 'MOVIMIENTOS POR VENDEDOR';
    protected int $totalColumnas = 7;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $controller = new ReporteController();
        return $controller->construirQuery('movimientos', $this->request)->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'FECHA Y HORA',
            'VENDEDOR',
            'SECCIÓN / SERVICIO',
            'TIPO',
            'MONTO (Bs)',
            'OBSERVACIÓN'
        ];
    }

    public function map($movimiento): array
    {
        $vendedor = $movimiento->aperturaCaja && $movimiento->aperturaCaja->usuario 
            ? $movimiento->aperturaCaja->usuario->nombre_completo 
            : 'Desconocido';

        return [
            $movimiento->id,
            Carbon::parse($movimiento->created_at)->format('d/m/Y H:i'),
            $vendedor,
            strtoupper(str_replace('_', ' ', $movimiento->seccion)),
            strtoupper($movimiento->tipo),
            number_format($movimiento->monto, 2),
            $movimiento->observacion ?? 'Sin observación'
        ];
    }

    public function registerEvents(): array
    {
        return $this->registrarCabecera();
    }
}
