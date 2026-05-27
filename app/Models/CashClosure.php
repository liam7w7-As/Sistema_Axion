<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\RegistraLog;

/**
 * Modelo: Cierre de Caja.
 * Consolida la información financiera al finalizar un turno.
 */
class CashClosure extends Model
{
    use HasFactory, SoftDeletes, RegistraLog;

    /**
     * Campos asignables masivamente.
     */
    protected $fillable = [
        'cash_opening_id',
        'fecha_hora_cierre',
        'saldo_inicial',
        'total_ventas',
        'total_movimientos',
        'saldo_esperado',
        'saldo_entregado',
        'sobrante',
        'faltante',
        'observacion',
        'status',
        'approved_by',
    ];

    /**
     * Conversión de tipos de atributos.
     */
    protected function casts(): array
    {
        return [
            'fecha_hora_cierre' => 'datetime',
            'saldo_inicial' => 'decimal:2',
            'total_ventas' => 'decimal:2',
            'total_movimientos' => 'decimal:2',
            'saldo_esperado' => 'decimal:2',
            'saldo_entregado' => 'decimal:2',
            'sobrante' => 'decimal:2',
            'faltante' => 'decimal:2',
        ];
    }

    // ========================
    // RELACIONES
    // ========================

    /**
     * Apertura de caja asociada a este cierre.
     */
    public function aperturaCaja()
    {
        return $this->belongsTo(CashOpening::class, 'cash_opening_id');
    }

    /**
     * Usuario que aprobó el cierre de caja.
     */
    public function aprobadoPor()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ========================
    // SCOPES
    // ========================

    /**
     * Scope: Solo cierres pendientes de aprobación.
     */
    public function scopePendientes($query)
    {
        return $query->where('status', 'pendiente');
    }

    /**
     * Scope: Solo cierres aprobados.
     */
    public function scopeAprobados($query)
    {
        return $query->where('status', 'aprobado');
    }
}
