<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('seller_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_opening_id')
                  ->constrained('cash_openings')
                  ->onDelete('cascade')
                  ->comment('Apertura de caja asociada');
            $table->enum('seccion', [
                'tarjetas_unidad',
                'tarjetas_mayor',
                'recuperaciones',
                'chips',
                'recargas',
                'megas',
                'servicios_digitales',
                'banca_digital',
                'servicio_tecnico',
                'efectivo_monedas'
            ])->comment('Sección del dashboard a la que pertenece el movimiento');
            $table->integer('cantidad')->nullable()->comment('Cantidad (ej. número de tarjetas)');
            $table->decimal('monto', 10, 2)->comment('Monto en Bs del movimiento');
            $table->text('observacion')->nullable()->comment('Observaciones opcionales');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_movements');
    }
};
