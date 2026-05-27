<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo: Inventario.
 * Registra los movimientos de stock de productos.
 */
class Inventory extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nombre de la tabla asociada.
     */
    protected $table = 'inventory';

    /**
     * Campos asignables masivamente.
     */
    protected $fillable = [
        'product_service_id',
        'tipo_inventario',
        'cantidad_ingreso',
        'stock_actual',
        'stock_previsto',
        'precio_compra',
        'fecha_hora',
        'tipo_movimiento',
        'observacion',
    ];

    /**
     * Conversión de tipos de atributos.
     */
    protected function casts(): array
    {
        return [
            'precio_compra' => 'decimal:2',
            'fecha_hora' => 'datetime',
            'cantidad_ingreso' => 'integer',
            'stock_actual' => 'integer',
            'stock_previsto' => 'integer',
        ];
    }

    // ========================
    // RELACIONES
    // ========================

    /**
     * Producto/servicio al que pertenece este movimiento.
     */
    public function productoServicio()
    {
        return $this->belongsTo(ProductService::class, 'product_service_id');
    }
}
