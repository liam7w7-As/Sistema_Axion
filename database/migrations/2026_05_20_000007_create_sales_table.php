<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Tabla de ventas.
 * Registra cada transacción de venta realizada en el sistema.
 * Relaciones: pertenece a un usuario, una apertura de caja y un producto/servicio.
 */
return new class extends Migration
{
    /**
     * Ejecutar la migración.
     */
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique()->comment('Código único de la venta');
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('restrict')
                  ->comment('Vendedor que realizó la venta');
            $table->foreignId('cash_opening_id')
                  ->constrained('cash_openings')
                  ->onDelete('restrict')
                  ->comment('Apertura de caja asociada');
            $table->dateTime('fecha_hora')->comment('Fecha y hora de la venta');
            $table->foreignId('product_service_id')
                  ->constrained('products_services')
                  ->onDelete('restrict')
                  ->comment('Producto o servicio vendido');
            $table->integer('cantidad')->default(1)->comment('Cantidad vendida');
            $table->decimal('precio_venta', 10, 2)->comment('Precio de venta unitario');
            $table->decimal('total', 10, 2)->comment('Total de la venta (cantidad * precio)');
            $table->enum('tipo_pago', ['efectivo', 'transferencia', 'qr', 'mixto'])->default('efectivo')->comment('Tipo de pago');
            $table->text('observacion')->nullable()->comment('Observaciones de la venta');
            $table->enum('status', ['completada', 'anulada', 'pendiente'])->default('completada')->comment('Estado de la venta');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Revertir la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
