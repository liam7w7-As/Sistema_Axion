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

class UsuariosExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    use ConCabeceraReporte;

    protected $request;
    protected string $tituloReporte = 'REPORTE DE USUARIOS';
    protected int $totalColumnas = 8;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $controller = new ReporteController();
        return $controller->construirQuery('usuarios', $this->request)->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'CÓDIGO',
            'NOMBRE COMPLETO',
            'EMAIL',
            'TELÉFONO',
            'ROLES',
            'ESTADO',
            'FECHA DE CREACIÓN'
        ];
    }

    public function map($usuario): array
    {
        $roles = $usuario->roles->pluck('name')->map(fn($r) => strtoupper($r))->implode(', ');

        return [
            $usuario->id,
            $usuario->codigo,
            $usuario->nombre_completo,
            $usuario->email,
            $usuario->telefono ?? 'N/A',
            $roles,
            strtoupper($usuario->estado),
            Carbon::parse($usuario->created_at)->format('d/m/Y H:i')
        ];
    }

    public function registerEvents(): array
    {
        return $this->registrarCabecera();
    }
}
