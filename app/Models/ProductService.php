<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\RegistraLog;

/**
 * Modelo: Producto o Servicio.
 * Gestiona el catálogo de productos y servicios de telefonía.
 */
class ProductService extends Model
{
    use HasFactory, SoftDeletes, RegistraLog;

    /**
     * Nombre de la tabla asociada.
     */
    protected $table = 'products_services';

    /**
     * Campos asignables masivamente.
     */
    protected $fillable = [
        'tipo',
        'nombre',
        'descripcion',
        'operador',
        'categoria',
        'seccion_reporte',
        'estado',
        'stock_actual',
        'unidad_venta',
        'precio_compra',
        'precio_venta',
        'tipo_ganancia',
        'ganancia',
        'comision',
        'imagen',
    ];

    /**
     * Conversión de tipos de atributos.
     */
    protected function casts(): array
    {
        return [
            'precio_compra' => 'decimal:2',
            'precio_venta' => 'decimal:2',
            'ganancia' => 'decimal:2',
            'comision' => 'decimal:2',
            'stock_actual' => 'integer',
        ];
    }

    // ========================
    // RELACIONES
    // ========================

    /**
     * Movimientos de inventario de este producto.
     */
    public function inventarios()
    {
        return $this->hasMany(Inventory::class, 'product_service_id');
    }

    /**
     * Ítems de venta asociados a este producto/servicio.
     */
    public function saleItems()
    {
        return $this->hasMany(SaleItem::class, 'product_service_id');
    }

    // ========================
    // SCOPES
    // ========================

    /**
     * Scope: Solo productos activos.
     */
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    /**
     * Scope: Solo productos (no servicios).
     */
    public function scopeProductos($query)
    {
        return $query->where('tipo', 'producto');
    }

    /**
     * Scope: Solo servicios (no productos).
     */
    public function scopeServicios($query)
    {
        return $query->where('tipo', 'servicio');
    }
}
