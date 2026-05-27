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

class ProductosExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    use ConCabeceraReporte;

    protected $request;
    protected string $tituloReporte = 'REPORTE DE PRODUCTOS Y SERVICIOS';
    protected int $totalColumnas = 10;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $controller = new ReporteController();
        return $controller->construirQuery('productos', $this->request)->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'NOMBRE',
            'TIPO',
            'CATEGORÍA / SECCIÓN',
            'OPERADOR',
            'PRECIO DE COMPRA (Bs)',
            'PRECIO DE VENTA (Bs)',
            'GANANCIA (Bs)',
            'STOCK',
            'ESTADO'
        ];
    }

    public function map($producto): array
    {
        return [
            $producto->id,
            $producto->nombre,
            strtoupper($producto->tipo),
            $producto->seccion_reporte ? strtoupper(str_replace('_', ' ', $producto->seccion_reporte)) : strtoupper($producto->categoria),
            $producto->operador ?? 'N/A',
            number_format($producto->precio_compra, 2),
            number_format($producto->precio_venta, 2),
            number_format($producto->ganancia, 2),
            $producto->tipo === 'producto' ? $producto->stock_actual : 'N/A',
            strtoupper($producto->estado)
        ];
    }

    public function registerEvents(): array
    {
        return $this->registrarCabecera();
    }
}
