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
            $table->index('estado');
        });

        Schema::table('products_services', function (Blueprint $table) {
            $table->index('tipo');
            $table->index('operador');
            $table->index('estado');
            $table->index('seccion_reporte');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->index('status');
            $table->index('fecha_hora');
        });

        Schema::table('cash_openings', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('cash_closures', function (Blueprint $table) {
            $table->index('status');
            $table->index('approved_by');
        });

        Schema::table('seller_movements', function (Blueprint $table) {
            $table->index('seccion');
        });

        Schema::table('inventory', function (Blueprint $table) {
            $table->index('tipo_movimiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['estado']);
        });

        Schema::table('products_services', function (Blueprint $table) {
            $table->dropIndex(['tipo']);
            $table->dropIndex(['operador']);
            $table->dropIndex(['estado']);
            $table->dropIndex(['seccion_reporte']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['fecha_hora']);
        });

        Schema::table('cash_openings', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('cash_closures', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['approved_by']);
        });

        Schema::table('seller_movements', function (Blueprint $table) {
            $table->dropIndex(['seccion']);
        });

        Schema::table('inventory', function (Blueprint $table) {
            $table->dropIndex(['tipo_movimiento']);
        });
    }
};
