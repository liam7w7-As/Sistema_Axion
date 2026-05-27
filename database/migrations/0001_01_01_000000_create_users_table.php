<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Tabla de usuarios del sistema.
 * Reemplaza la tabla original de Breeze con los campos del ERS.
 * Se mantiene password_reset_tokens y sessions para compatibilidad.
 */
return new class extends Migration
{
    /**
     * Ejecutar la migración.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_completo');
            $table->string('codigo')->unique()->comment('Código único de acceso del usuario');
            $table->string('password');
            $table->enum('role', ['admin', 'seller'])->default('seller')->comment('Rol del usuario');
            $table->string('foto')->nullable()->comment('Ruta de la foto del usuario');
            $table->enum('estado', ['habilitado', 'deshabilitado'])->default('habilitado');
            $table->json('servicios_asignados_json')->nullable()->comment('Servicios asignados al vendedor en formato JSON');
            $table->boolean('visualizar_ganancias')->default(false)->comment('Permiso para ver ganancias');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Revertir la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
