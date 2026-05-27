<?php

namespace App\Exports\Concerns;

use Carbon\Carbon;
use Maatwebsite\Excel\Events\AfterSheet;

/**
 * Trait reutilizable que agrega cabecera corporativa a los reportes Excel.
 * Cada Export que lo use debe definir:
 *   - protected string $tituloReporte
 *   - protected int $totalColumnas (número de columnas del reporte)
 */
trait ConCabeceraReporte
{
    /**
     * Registra el evento AfterSheet para inyectar la cabecera.
     */
    protected function registrarCabecera(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $ultimaCol = $this->getLetraColumna($this->totalColumnas);

                // Insertar 5 filas vacías al inicio (empuja datos hacia abajo)
                $sheet->insertNewRowBefore(1, 5);

                // Construir texto de período
                $fechaInicio = $this->request->fecha_inicio ?? $this->request->input('fecha_inicio');
                $fechaFin = $this->request->fecha_fin ?? $this->request->input('fecha_fin');
                $periodoTexto = ($fechaInicio && $fechaFin)
                    ? Carbon::parse($fechaInicio)->format('d/m/Y') . ' al ' . Carbon::parse($fechaFin)->format('d/m/Y')
                    : 'Todos los registros';

                // Fila 1: Título del reporte
                $sheet->setCellValue('A1', 'SISTEMA DE TELEFONÍA - ' . ($this->tituloReporte ?? 'REPORTE'));
                $sheet->mergeCells("A1:{$ultimaCol}1");
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                // Fila 2: Fecha de generación
                $sheet->setCellValue('A2', 'Fecha de generación: ' . Carbon::now()->format('d/m/Y H:i:s'));
                $sheet->mergeCells("A2:{$ultimaCol}2");
                $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10);

                // Fila 3: Período
                $sheet->setCellValue('A3', 'Período: ' . $periodoTexto);
                $sheet->mergeCells("A3:{$ultimaCol}3");
                $sheet->getStyle('A3')->getFont()->setItalic(true)->setSize(10);

                // Fila 4: Vacía (separador)

                // Estilo de los encabezados de columna (fila 6 tras insertar 5 filas)
                $sheet->getStyle("A6:{$ultimaCol}6")->getFont()->setBold(true);
                $sheet->getStyle("A6:{$ultimaCol}6")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('E2E8F0');

                // Bordes en los encabezados
                $sheet->getStyle("A6:{$ultimaCol}6")->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            },
        ];
    }

    /**
     * Convierte un número de columna (1-based) a letra de Excel (A, B, ..., Z, AA, AB...).
     */
    private function getLetraColumna(int $num): string
    {
        $letter = '';
        while ($num > 0) {
            $num--;
            $letter = chr(65 + ($num % 26)) . $letter;
            $num = intdiv($num, 26);
        }
        return $letter;
    }
}
