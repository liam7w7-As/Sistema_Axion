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
            if (!Schema::hasColumn('products_services', 'stock_actual')) {
                $table->integer('stock_actual')->default(0)->after('estado')->comment('Stock físico actual del producto');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products_services', function (Blueprint $table) {
            if (Schema::hasColumn('products_services', 'stock_actual')) {
                $table->dropColumn('stock_actual');
            }
        });
    }
};
