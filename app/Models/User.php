<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Traits\RegistraLog;

/**
 * Modelo: Usuario del sistema.
 * Gestiona administradores y vendedores con autenticación por código.
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasRoles, RegistraLog;

    /**
     * Campos asignables masivamente.
     */
    protected $fillable = [
        'nombre_completo',
        'codigo',
        'password',
        'role',
        'foto',
        'estado',
        'servicios_asignados_json',
        'visualizar_ganancias',
        'omitir_bloqueo_horario',
    ];

    /**
     * Campos ocultos en serialización.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Conversión de tipos de atributos.
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'servicios_asignados_json' => 'array',
            'visualizar_ganancias' => 'boolean',
            'omitir_bloqueo_horario' => 'boolean',
        ];
    }

    // ========================
    // RELACIONES
    // ========================

    /**
     * Aperturas de caja realizadas por este usuario.
     */
    public function aperturasCaja()
    {
        return $this->hasMany(CashOpening::class);
    }

    /**
     * Ventas realizadas por este usuario.
     */
    public function ventas()
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Cierres de caja aprobados por este usuario (como administrador).
     */
    public function cierresAprobados()
    {
        return $this->hasMany(CashClosure::class, 'approved_by');
    }

    /**
     * Paneles de vendedor asociados a este usuario.
     */
    public function panelVendedor()
    {
        return $this->hasMany(SellerDashboard::class);
    }

    /**
     * Registros de log de este usuario.
     */
    public function logs()
    {
        return $this->hasMany(Log::class);
    }

    // ========================
    // SCOPES
    // ========================

    /**
     * Scope: Solo usuarios habilitados.
     */
    public function scopeHabilitados($query)
    {
        return $query->where('estado', 'habilitado');
    }

    /**
     * Scope: Solo administradores.
     */
    public function scopeAdministradores($query)
    {
        return $query->where('role', 'admin');
    }

    /**
     * Scope: Solo vendedores.
     */
    public function scopeVendedores($query)
    {
        return $query->where('role', 'seller');
    }
}
