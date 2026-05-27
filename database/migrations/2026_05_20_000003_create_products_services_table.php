<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Tabla de productos y servicios.
 * Almacena el catálogo completo de productos y servicios de telefonía.
 */
return new class extends Migration
{
    /**
     * Ejecutar la migración.
     */
    public function up(): void
    {
        Schema::create('products_services', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['producto', 'servicio'])->comment('Tipo: producto o servicio');
            $table->string('nombre')->comment('Nombre del producto o servicio');
            $table->text('descripcion')->nullable()->comment('Descripción detallada');
            $table->string('operador')->nullable()->comment('Operador de telefonía asociado');
            $table->string('categoria')->nullable()->comment('Categoría del producto/servicio');
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->string('unidad_venta')->nullable()->comment('Unidad de venta: unidad, paquete, etc.');
            $table->decimal('precio_compra', 10, 2)->default(0)->comment('Precio de compra');
            $table->decimal('precio_venta', 10, 2)->default(0)->comment('Precio de venta al público');
            $table->decimal('ganancia', 10, 2)->default(0)->comment('Ganancia por unidad');
            $table->decimal('comision', 10, 2)->default(0)->comment('Comisión por venta');
            $table->string('imagen')->nullable()->comment('Ruta de la imagen del producto');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Revertir la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('products_services');
    }
};
