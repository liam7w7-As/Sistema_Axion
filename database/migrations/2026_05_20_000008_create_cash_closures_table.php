<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Tabla de cierres de caja.
 * Registra el cierre de una sesión de caja con totales, sobrantes y faltantes.
 * Relación: pertenece a una apertura de caja.
 */
return new class extends Migration
{
    /**
     * Ejecutar la migración.
     */
    public function up(): void
    {
        Schema::create('cash_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_opening_id')
                  ->constrained('cash_openings')
                  ->onDelete('restrict')
                  ->comment('Apertura de caja asociada');
            $table->dateTime('fecha_hora_cierre')->comment('Fecha y hora del cierre');
            $table->decimal('saldo_inicial', 10, 2)->default(0)->comment('Saldo inicial de la caja');
            $table->decimal('total_ventas', 10, 2)->default(0)->comment('Total de ventas realizadas');
            $table->decimal('total_movimientos', 10, 2)->default(0)->comment('Total de movimientos adicionales');
            $table->decimal('saldo_esperado', 10, 2)->default(0)->comment('Saldo esperado al cierre');
            $table->decimal('saldo_entregado', 10, 2)->default(0)->comment('Saldo entregado por el vendedor');
            $table->decimal('sobrante', 10, 2)->default(0)->comment('Sobrante de caja');
            $table->decimal('faltante', 10, 2)->default(0)->comment('Faltante de caja');
            $table->text('observacion')->nullable()->comment('Observaciones del cierre');
            $table->enum('status', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente')->comment('Estado de aprobación del cierre');
            $table->foreignId('approved_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('restrict')
                  ->comment('Usuario que aprobó el cierre');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Revertir la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_closures');
    }
};
