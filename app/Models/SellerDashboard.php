<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo: Panel del Vendedor.
 * Almacena totales por categoría de servicio durante una sesión de caja.
 */
class SellerDashboard extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nombre de la tabla asociada.
     */
    protected $table = 'seller_dashboard';

    /**
     * Campos asignables masivamente.
     */
    protected $fillable = [
        'user_id',
        'cash_opening_id',
        'tarjetas_unidad',
        'tarjetas_mayor',
        'recuperaciones',
        'chips',
        'recargas',
        'megas',
        'servicios_digitales',
        'banca_digital',
        'servicio_tecnico',
        'efectivo_monedas',
    ];

    /**
     * Conversión de tipos de atributos.
     */
    protected function casts(): array
    {
        return [
            'tarjetas_unidad' => 'decimal:2',
            'tarjetas_mayor' => 'decimal:2',
            'recuperaciones' => 'decimal:2',
            'chips' => 'decimal:2',
            'recargas' => 'decimal:2',
            'megas' => 'decimal:2',
            'servicios_digitales' => 'decimal:2',
            'banca_digital' => 'decimal:2',
            'servicio_tecnico' => 'decimal:2',
            'efectivo_monedas' => 'decimal:2',
        ];
    }

    // ========================
    // RELACIONES
    // ========================

    /**
     * Vendedor asociado a este panel.
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Apertura de caja asociada a este panel.
     */
    public function aperturaCaja()
    {
        return $this->belongsTo(CashOpening::class, 'cash_opening_id');
    }
}
