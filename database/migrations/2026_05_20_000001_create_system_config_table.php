<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Tabla de configuración general del sistema.
 * Almacena nombre, logo, horarios, moneda y formatos de impresión.
 */
return new class extends Migration
{
    /**
     * Ejecutar la migración.
     */
    public function up(): void
    {
        Schema::create('system_config', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_sistema')->comment('Nombre completo del sistema');
            $table->string('alias')->comment('Nombre corto o alias del sistema');
            $table->string('logo')->nullable()->comment('Ruta del logo del sistema');
            $table->string('actividad')->nullable()->comment('Actividad o giro del negocio');
            $table->string('moneda', 10)->default('Bs')->comment('Moneda utilizada');
            $table->time('hora_inicio_admin')->nullable()->comment('Hora de inicio para administradores');
            $table->time('hora_fin_admin')->nullable()->comment('Hora de fin para administradores');
            $table->time('hora_inicio_vendedor')->nullable()->comment('Hora de inicio para vendedores');
            $table->time('hora_fin_vendedor')->nullable()->comment('Hora de fin para vendedores');
            $table->string('formato_impresion')->default('thermal_58mm')->comment('Formato de impresión de recibos');
            $table->string('tamano_papel_thermal', 20)->default('58mm')->comment('Tamaño del papel térmico');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Revertir la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_config');
    }
};
