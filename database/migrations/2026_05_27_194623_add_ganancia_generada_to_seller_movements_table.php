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
        Schema::table('seller_movements', function (Blueprint $table) {
            $table->decimal('ganancia_generada', 10, 2)->nullable()->default(0)->after('monto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seller_movements', function (Blueprint $table) {
            $table->dropColumn('ganancia_generada');
        });
    }
};
