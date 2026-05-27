<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo: Ítem de Venta.
 * Cada línea del carrito de una venta.
 */
class SaleItem extends Model
{
    use HasFactory;

    protected $table = 'sale_items';

    protected $fillable = [
        'sale_id',
        'product_service_id',
        'cantidad',
        'precio_venta',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'precio_venta' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    /**
     * Venta a la que pertenece este ítem.
     */
    public function venta()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    /**
     * Producto o servicio vendido en este ítem.
     */
    public function productoServicio()
    {
        return $this->belongsTo(ProductService::class, 'product_service_id');
    }
}
