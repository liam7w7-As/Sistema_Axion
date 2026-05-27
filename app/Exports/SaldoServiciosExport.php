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

class SaldoServiciosExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    use ConCabeceraReporte;

    protected $request;
    protected string $tituloReporte = 'SALDO GENERAL POR SERVICIOS';
    protected int $totalColumnas = 13;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $controller = new ReporteController();
        return $controller->construirQuery('saldos', $this->request)->get();
    }

    public function headings(): array
    {
        return [
            'ID CIERRE',
            'FECHA',
            'VENDEDOR',
            'TARJETAS X UNIDAD (Bs)',
            'TARJETAS MAYOR (Bs)',
            'RECUPERACIONES (Bs)',
            'CHIPS (Bs)',
            'RECARGAS (Bs)',
            'MEGAS (Bs)',
            'SERVICIOS DIGITALES (Bs)',
            'BANCA DIGITAL (Bs)',
            'SERVICIO TÉCNICO (Bs)',
            'EFECTIVO MONEDAS (Bs)'
        ];
    }

    public function map($saldo): array
    {
        return [
            $saldo->cash_opening_id,
            Carbon::parse($saldo->created_at)->format('d/m/Y'),
            $saldo->usuario ? $saldo->usuario->nombre_completo : 'Desconocido',
            number_format($saldo->tarjetas_unidad, 2),
            number_format($saldo->tarjetas_mayor, 2),
            number_format($saldo->recuperaciones, 2),
            number_format($saldo->chips, 2),
            number_format($saldo->recargas, 2),
            number_format($saldo->megas, 2),
            number_format($saldo->servicios_digitales, 2),
            number_format($saldo->banca_digital, 2),
            number_format($saldo->servicio_tecnico, 2),
            number_format($saldo->efectivo_monedas, 2)
        ];
    }

    public function registerEvents(): array
    {
        return $this->registrarCabecera();
    }
}
