<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\RegistraLog;

/**
 * Modelo: Venta.
 * Registra cada transacción de venta realizada en el sistema.
 */
class Sale extends Model
{
    use HasFactory, SoftDeletes, RegistraLog;

    /**
     * Campos asignables masivamente.
     */
    protected $fillable = [
        'codigo',
        'user_id',
        'cash_opening_id',
        'fecha_hora',
        'product_service_id',
        'cantidad',
        'precio_venta',
        'total',
        'tipo_pago',
        'observacion',
        'status',
        'motivo_anulacion',
    ];

    /**
     * Conversión de tipos de atributos.
     */
    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
            'precio_venta' => 'decimal:2',
            'total' => 'decimal:2',
            'cantidad' => 'integer',
        ];
    }

    // ========================
    // RELACIONES
    // ========================

    /**
     * Ítems (líneas del carrito) de esta venta.
     */
    public function items()
    {
        return $this->hasMany(SaleItem::class, 'sale_id');
    }

    /**
     * Vendedor que realizó la venta.
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Apertura de caja donde se registró la venta.
     */
    public function aperturaCaja()
    {
        return $this->belongsTo(CashOpening::class, 'cash_opening_id');
    }

    /**
     * Producto o servicio vendido (legacy, para ventas de un solo producto).
     */
    public function productoServicio()
    {
        return $this->belongsTo(ProductService::class, 'product_service_id');
    }

    // ========================
    // SCOPES
    // ========================

    /**
     * Scope: Solo ventas completadas.
     */
    public function scopeCompletadas($query)
    {
        return $query->where('status', 'completada');
    }

    /**
     * Scope: Solo ventas anuladas.
     */
    public function scopeAnuladas($query)
    {
        return $query->where('status', 'anulada');
    }

    /**
     * Scope: Ventas de hoy.
     */
    public function scopeDeHoy($query)
    {
        return $query->whereDate('fecha_hora', now()->toDateString());
    }
}
