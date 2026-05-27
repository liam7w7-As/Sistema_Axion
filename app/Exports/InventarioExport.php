<?php

namespace App\Exports;

use Illuminate\Http\Request;
use App\Models\ProductService;
use App\Exports\Concerns\ConCabeceraReporte;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;

class InventarioExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    use ConCabeceraReporte;

    protected $request;
    protected string $tituloReporte = 'REPORTE DE INVENTARIO';
    protected int $totalColumnas = 6;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        return ProductService::with('inventarios')
            ->when($this->request->tipo, function ($q) {
                return $q->where('tipo', $this->request->tipo);
            })
            ->when($this->request->categoria, function ($q) {
                return $q->where('categoria', $this->request->categoria);
            })
            ->when($this->request->operador, function ($q) {
                return $q->where('operador', $this->request->operador);
            })
            ->orderBy('nombre', 'asc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Producto/Servicio',
            'Tipo',
            'Operador',
            'Precio Compra',
            'Stock Actual'
        ];
    }

    public function map($inv): array
    {
        return [
            $inv->id,
            $inv->nombre,
            strtoupper($inv->tipo),
            $inv->operador,
            $inv->precio_compra,
            $inv->tipo === 'producto' ? $inv->stock_actual : 'N/A',
        ];
    }

    public function registerEvents(): array
    {
        return $this->registrarCabecera();
    }
}
