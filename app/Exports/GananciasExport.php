<?php

namespace App\Exports;

use App\Http\Controllers\ReporteController;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GananciasExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    protected $request;
    protected $datos;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $controller = new ReporteController();
        $this->datos = $controller->construirQuery('ganancias', $this->request)->get();
        return $this->datos;
    }

    public function headings(): array
    {
        return [
            'PRODUCTO / SERVICIO',
            'OPERADOR',
            'PRECIO COMPRA (Bs)',
            'CANTIDAD VENDIDA',
            'INGRESOS (Bs)',
            'GANANCIA NETA (Bs)',
        ];
    }

    public function map($ganancia): array
    {
        return [
            $ganancia->producto,
            $ganancia->operador ?: '—',
            number_format($ganancia->precio_compra, 2),
            $ganancia->cantidad_total,
            number_format($ganancia->ingresos_totales, 2),
            number_format($ganancia->ganancia_neta, 2),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Calcular totales
                $totalCantidad = $this->datos ? $this->datos->sum('cantidad_total') : 0;
                $totalIngresos = $this->datos ? $this->datos->sum('ingresos_totales') : 0;
                $totalGanancia = $this->datos ? $this->datos->sum('ganancia_neta') : 0;

                // Obtener info de filtros
                $fechaInicio = $this->request->fecha_inicio;
                $fechaFin = $this->request->fecha_fin;
                $periodoTexto = ($fechaInicio && $fechaFin)
                    ? Carbon::parse($fechaInicio)->format('d/m/Y') . ' al ' . Carbon::parse($fechaFin)->format('d/m/Y')
                    : 'Todos los registros';

                // Insertar filas de encabezado al inicio (desplaza datos hacia abajo)
                $sheet->insertNewRowBefore(1, 5);

                // Encabezado informativo
                $sheet->setCellValue('A1', 'SISTEMA DE TELEFONÍA - REPORTE DE GANANCIAS');
                $sheet->setCellValue('A2', 'Fecha de generación: ' . Carbon::now()->format('d/m/Y H:i:s'));
                $sheet->setCellValue('A3', 'Período: ' . $periodoTexto);
                $sheet->setCellValue('A4', '');

                // Estilos del encabezado
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10);
                $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10);

                // Merge del título
                $sheet->mergeCells('A1:F1');
                $sheet->mergeCells('A2:F2');
                $sheet->mergeCells('A3:F3');

                // Estilo de los encabezados de columna (fila 6 tras insertar 5 filas)
                $sheet->getStyle('A6:F6')->getFont()->setBold(true);
                $sheet->getStyle('A6:F6')->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('E2E8F0');

                // Fila de totales al final
                $lastRow = $sheet->getHighestRow() + 1;
                $sheet->setCellValue('A' . $lastRow, '');
                $lastRow++;
                $sheet->setCellValue('A' . $lastRow, 'TOTALES:');
                $sheet->setCellValue('D' . $lastRow, $totalCantidad);
                $sheet->setCellValue('E' . $lastRow, number_format($totalIngresos, 2));
                $sheet->setCellValue('F' . $lastRow, number_format($totalGanancia, 2));
                $sheet->getStyle('A' . $lastRow . ':F' . $lastRow)->getFont()->setBold(true);
                $sheet->getStyle('A' . $lastRow . ':F' . $lastRow)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('E2E8F0');

                // Nota al pie
                $noteRow = $lastRow + 2;
                $sheet->setCellValue('A' . $noteRow, '* Los costos de ventas anteriores al 26/05/2026 se calcularon con el precio de compra vigente al momento de la migración.');
                $sheet->getStyle('A' . $noteRow)->getFont()->setItalic(true)->setSize(8)->getColor()->setRGB('666666');
                $sheet->mergeCells('A' . $noteRow . ':F' . $noteRow);
            },
        ];
    }
}
