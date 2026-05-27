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
        Schema::table('users', function (Blueprint $table) {
            // Verificar si visualizar_ganancias ya existe (lo agregamos en la base, pero por si acaso)
            if (!Schema::hasColumn('users', 'visualizar_ganancias')) {
                $table->boolean('visualizar_ganancias')->default(false)->after('servicios_asignados_json')->comment('Permiso para ver ganancias');
            }
            
            if (!Schema::hasColumn('users', 'omitir_bloqueo_horario')) {
                $table->boolean('omitir_bloqueo_horario')->default(false)->after('estado')->comment('Si es true, el usuario puede acceder fuera de su horario');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'omitir_bloqueo_horario')) {
                $table->dropColumn('omitir_bloqueo_horario');
            }
            // No eliminamos visualizar_ganancias aquí porque era parte de la migración original
        });
    }
};
