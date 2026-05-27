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
        Schema::table('cash_openings', function (Blueprint $table) {
            $table->json('limites_servicios_json')->nullable()->after('servicios_asignados_json')->comment('Límites en Bs asignados por servicio y operador');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_openings', function (Blueprint $table) {
            $table->dropColumn('limites_servicios_json');
        });
    }
};
