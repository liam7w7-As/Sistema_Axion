<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Tabla de logs del sistema (Nota 3 del ERS).
 * Registra todas las acciones relevantes realizadas por los usuarios.
 * NO usa soft deletes - los logs son permanentes e inalterables.
 */
return new class extends Migration
{
    /**
     * Ejecutar la migración.
     */
    public function up(): void
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('restrict')
                  ->comment('Usuario que realizó la acción');
            $table->string('accion')->comment('Acción realizada: crear, editar, eliminar, login, etc.');
            $table->string('modelo')->nullable()->comment('Modelo afectado: User, Sale, etc.');
            $table->unsignedBigInteger('modelo_id')->nullable()->comment('ID del registro afectado');
            $table->json('valores_antiguos')->nullable()->comment('Valores anteriores al cambio');
            $table->json('valores_nuevos')->nullable()->comment('Valores nuevos después del cambio');
            $table->timestamp('created_at')->useCurrent()->comment('Fecha y hora de la acción');
            // Sin updated_at ni soft deletes - los logs son inmutables
        });
    }

    /**
     * Revertir la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
