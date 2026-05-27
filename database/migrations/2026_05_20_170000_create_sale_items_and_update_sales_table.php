<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Tabla de ítems de venta y actualización de tabla sales.
 * Permite ventas con múltiples productos (carrito).
 * Añade motivo de anulación a la tabla sales.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Crear tabla de ítems de venta
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')
                  ->constrained('sales')
                  ->onDelete('cascade')
                  ->comment('Venta a la que pertenece este ítem');
            $table->foreignId('product_service_id')
                  ->constrained('products_services')
                  ->onDelete('restrict')
                  ->comment('Producto o servicio vendido');
            $table->integer('cantidad')->default(1)->comment('Cantidad vendida');
            $table->decimal('precio_venta', 10, 2)->comment('Precio unitario al momento de la venta');
            $table->decimal('subtotal', 10, 2)->comment('Subtotal = cantidad * precio_venta');
            $table->timestamps();
        });

        // 2. Añadir motivo_anulacion a sales y eliminar campos legacy
        Schema::table('sales', function (Blueprint $table) {
            $table->text('motivo_anulacion')->nullable()->after('status')->comment('Motivo de anulación de la venta');
            
            // Eliminar campos que ahora viven en sale_items
            $table->dropForeign(['product_service_id']);
            $table->dropColumn(['product_service_id', 'cantidad', 'precio_venta']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('motivo_anulacion');
        });
    }
};
