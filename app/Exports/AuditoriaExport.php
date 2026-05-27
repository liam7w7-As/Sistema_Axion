<?php

namespace App\Exports;

use Illuminate\Http\Request;
use App\Models\Log;
use App\Exports\Concerns\ConCabeceraReporte;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Carbon\Carbon;

class AuditoriaExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    use ConCabeceraReporte;

    protected $request;
    protected string $tituloReporte = 'REPORTE DE AUDITORÍA';
    protected int $totalColumnas = 7;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection()
    {
        return Log::with('user')
            ->when($this->request->fecha_inicio && $this->request->fecha_fin, function ($q) {
                return $q->whereBetween('created_at', [$this->request->fecha_inicio, $this->request->fecha_fin]);
            })
            ->when($this->request->usuario_id, function ($q) {
                return $q->where('user_id', $this->request->usuario_id);
            })
            ->when($this->request->accion, function ($q) {
                return $q->where('accion', $this->request->accion);
            })
            ->when($this->request->modelo, function ($q) {
                return $q->where('modelo', $this->request->modelo);
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Fecha y Hora',
            'Usuario',
            'Acción',
            'Módulo Afectado',
            'ID Registro',
            'IP Address'
        ];
    }

    public function map($log): array
    {
        return [
            $log->id,
            Carbon::parse($log->created_at)->format('d/m/Y H:i:s'),
            $log->user ? $log->user->nombre_completo : 'Sistema',
            strtoupper($log->accion),
            class_basename($log->modelo),
            $log->modelo_id,
            $log->ip_address,
        ];
    }

    public function registerEvents(): array
    {
        return $this->registrarCabecera();
    }
}
