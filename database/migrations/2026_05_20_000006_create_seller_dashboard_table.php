<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Tabla de panel del vendedor (dashboard vendedor).
 * Almacena los conteos y montos de cada categoría de servicio
 * durante una sesión de caja.
 * Relación: pertenece a un usuario y a una apertura de caja.
 */
return new class extends Migration
{
    /**
     * Ejecutar la migración.
     */
    public function up(): void
    {
        Schema::create('seller_dashboard', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('restrict')
                  ->comment('Vendedor asociado');
            $table->foreignId('cash_opening_id')
                  ->constrained('cash_openings')
                  ->onDelete('cascade')
                  ->comment('Apertura de caja asociada');
            $table->decimal('tarjetas_unidad', 10, 2)->default(0)->comment('Total tarjetas por unidad');
            $table->decimal('tarjetas_mayor', 10, 2)->default(0)->comment('Total tarjetas al por mayor');
            $table->decimal('recuperaciones', 10, 2)->default(0)->comment('Total recuperaciones');
            $table->decimal('chips', 10, 2)->default(0)->comment('Total chips');
            $table->decimal('recargas', 10, 2)->default(0)->comment('Total recargas');
            $table->decimal('megas', 10, 2)->default(0)->comment('Total megas/datos');
            $table->decimal('servicios_digitales', 10, 2)->default(0)->comment('Total servicios digitales');
            $table->decimal('banca_digital', 10, 2)->default(0)->comment('Total banca digital');
            $table->decimal('servicio_tecnico', 10, 2)->default(0)->comment('Total servicio técnico');
            $table->decimal('efectivo_monedas', 10, 2)->default(0)->comment('Total efectivo en monedas');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Revertir la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_dashboard');
    }
};
