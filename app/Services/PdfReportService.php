<?php

namespace App\Services;

use TCPDF;
use App\Models\SystemConfig;

class PdfReportService extends TCPDF
{
    protected $tituloReporte;
    protected $nombreSistema;
    protected $logoPath;

    public function __construct($orientacion = 'P', $tituloReporte = 'REPORTE')
    {
        // Orientación: L (Horizontal), P (Vertical)
        // Medidas: mm
        // Formato: LETTER
        parent::__construct($orientacion, 'mm', 'LETTER', true, 'UTF-8', false);

        $config = SystemConfig::first();
        $this->nombreSistema = $config->nombre_sistema ?? 'SISTEF';
        $this->tituloReporte = mb_strtoupper($tituloReporte);
        
        // Asignar el logo si existe
        $this->logoPath = $config->logo && \Storage::disk('public')->exists($config->logo) 
            ? storage_path('app/public/' . $config->logo) 
            : null;

        // Configuración de documentos
        $this->SetCreator(PDF_CREATOR);
        $this->SetAuthor($this->nombreSistema);
        $this->SetTitle($this->tituloReporte);

        // Configuración de cabecera y pie
        $this->setPrintHeader(true);
        $this->setPrintFooter(true);

        // Márgenes corporativos (Izq, Sup, Der)
        $this->SetMargins(15, 28, 15);
        $this->SetHeaderMargin(10);
        $this->SetFooterMargin(10);

        // Salto de página automático
        $this->SetAutoPageBreak(TRUE, 15);

        // Añadir página
        $this->AddPage();
    }

    // Cabecera personalizada
    public function Header()
    {
        if ($this->logoPath) {
            // TCPDF Image: file, x, y, w, h
            $this->Image($this->logoPath, 15, 8, 16, '', '', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }

        // Título del Sistema
        $this->SetFont('helvetica', 'B', 12);
        $this->SetXY(15, 10);
        $this->Cell(0, 6, $this->nombreSistema, 0, false, 'C', 0, '', 0, false, 'M', 'M');

        // Título del Reporte
        $this->SetFont('helvetica', 'B', 10);
        $this->SetXY(15, 16);
        $this->Cell(0, 6, $this->tituloReporte, 0, false, 'C', 0, '', 0, false, 'M', 'M');

        // Línea divisoria
        $this->Line(15, 24, $this->getPageWidth() - 15, 24);
    }

    // Pie de página personalizado
    public function Footer()
    {
        // Posición a 15 mm del final
        $this->SetY(-15);
        
        // Línea superior fina
        $this->Line(15, $this->GetY(), $this->getPageWidth() - 15, $this->GetY());
        
        $this->SetFont('helvetica', 'I', 8);
        $this->SetY(-13);
        
        $fechaGeneracion = 'Generado: ' . now()->format('d/m/Y H:i');
        
        // Fecha a la izquierda
        $this->Cell(0, 10, $fechaGeneracion, 0, false, 'L', 0, '', 0, false, 'T', 'M');
        
        // Página X de Y al centro
        $this->SetX(15);
        $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'T', 'M');
    }

    /**
     * Generar la tabla a partir de cabeceras y filas, o usar HTML directamente.
     */
    public function renderHtmlTable($html)
    {
        $this->SetFont('helvetica', '', 9);
        $this->writeHTML($html, true, false, true, false, '');
    }

    public function renderHtml($html)
    {
        $this->SetFont('helvetica', '', 10);
        $this->writeHTML($html, true, false, true, false, '');
    }
}
