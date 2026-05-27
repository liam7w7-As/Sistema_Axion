<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Añade el campo precio_compra a sale_items para registrar el costo histórico
 * al momento de cada venta. Los registros legacy se rellenan con el precio_compra
 * vigente del producto asociado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('precio_compra', 10, 2)
                  ->default(0)
                  ->after('precio_venta')
                  ->comment('Precio de compra unitario al momento de la venta');
        });

        // Backfill: copiar el precio_compra actual del producto a ventas existentes
        DB::statement('
            UPDATE sale_items
            INNER JOIN products_services ON sale_items.product_service_id = products_services.id
            SET sale_items.precio_compra = products_services.precio_compra
            WHERE sale_items.precio_compra = 0
        ');
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('precio_compra');
        });
    }
};
