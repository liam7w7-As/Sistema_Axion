<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Tabla de inventario.
 * Registra movimientos de stock de productos (ingresos, egresos, ajustes).
 * Relación: pertenece a un producto/servicio.
 */
return new class extends Migration
{
    /**
     * Ejecutar la migración.
     */
    public function up(): void
    {
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_service_id')
                  ->constrained('products_services')
                  ->onDelete('restrict')
                  ->comment('Producto/servicio asociado');
            $table->enum('tipo_inventario', ['fisico', 'digital'])->default('fisico')->comment('Tipo de inventario');
            $table->integer('cantidad_ingreso')->default(0)->comment('Cantidad ingresada en este movimiento');
            $table->integer('stock_actual')->default(0)->comment('Stock actual después del movimiento');
            $table->integer('stock_previsto')->default(0)->comment('Stock previsto o esperado');
            $table->decimal('precio_compra', 10, 2)->default(0)->comment('Precio de compra al momento del movimiento');
            $table->dateTime('fecha_hora')->comment('Fecha y hora del movimiento');
            $table->enum('tipo_movimiento', ['ingreso', 'egreso', 'ajuste'])->comment('Tipo de movimiento de inventario');
            $table->text('observacion')->nullable()->comment('Observaciones del movimiento');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Revertir la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
