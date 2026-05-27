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
        Schema::table('products_services', function (Blueprint $table) {
            $table->string('seccion_reporte')->nullable()->after('categoria')->comment('Sección donde se agrupa en dashboard/cierre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products_services', function (Blueprint $table) {
            $table->dropColumn('seccion_reporte');
        });
    }
};
