<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\RegistraLog;

/**
 * Modelo: Apertura de Caja.
 * Representa una sesión o turno de trabajo de un vendedor.
 */
class CashOpening extends Model
{
    use HasFactory, SoftDeletes, RegistraLog;

    /**
     * Campos asignables masivamente.
     */
    protected $fillable = [
        'user_id',
        'fecha_hora_apertura',
        'saldo_inicial',
        'limite_venta',
        'servicios_asignados_json',
        'observacion',
        'status',
    ];

    /**
     * Conversión de tipos de atributos.
     */
    protected function casts(): array
    {
        return [
            'fecha_hora_apertura' => 'datetime',
            'saldo_inicial' => 'decimal:2',
            'limite_venta' => 'decimal:2',
            'servicios_asignados_json' => 'array',
        ];
    }

    // ========================
    // RELACIONES
    // ========================

    /**
     * Usuario que realizó la apertura de caja.
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Ventas realizadas durante esta apertura de caja.
     */
    public function ventas()
    {
        return $this->hasMany(Sale::class, 'cash_opening_id');
    }

    /**
     * Cierre de caja asociado a esta apertura.
     */
    public function cierreCaja()
    {
        return $this->hasOne(CashClosure::class, 'cash_opening_id');
    }

    /**
     * Panel de vendedor asociado a esta apertura.
     */
    public function panelVendedor()
    {
        return $this->hasMany(SellerDashboard::class, 'cash_opening_id');
    }

    // ========================
    // SCOPES
    // ========================

    /**
     * Scope: Solo cajas abiertas.
     */
    public function scopeAbiertas($query)
    {
        return $query->where('status', 'abierta');
    }

    /**
     * Scope: Solo cajas cerradas.
     */
    public function scopeCerradas($query)
    {
        return $query->where('status', 'cerrada');
    }
}
