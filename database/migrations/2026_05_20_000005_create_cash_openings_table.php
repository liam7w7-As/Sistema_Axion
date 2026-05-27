<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Tabla de aperturas de caja.
 * Registra cada apertura de caja realizada por un vendedor.
 * Relación: pertenece a un usuario.
 */
return new class extends Migration
{
    /**
     * Ejecutar la migración.
     */
    public function up(): void
    {
        Schema::create('cash_openings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('restrict')
                  ->comment('Usuario que realizó la apertura');
            $table->dateTime('fecha_hora_apertura')->comment('Fecha y hora de la apertura de caja');
            $table->decimal('saldo_inicial', 10, 2)->default(0)->comment('Saldo inicial de la caja');
            $table->decimal('limite_venta', 10, 2)->nullable()->comment('Límite de venta asignado');
            $table->json('servicios_asignados_json')->nullable()->comment('Servicios habilitados para esta apertura');
            $table->text('observacion')->nullable()->comment('Observaciones de la apertura');
            $table->enum('status', ['abierta', 'cerrada'])->default('abierta')->comment('Estado de la caja');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Revertir la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_openings');
    }
};
