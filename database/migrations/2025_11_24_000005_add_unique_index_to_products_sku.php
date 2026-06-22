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
        Schema::table('products', function (Blueprint $table) {
            // Solo crear el índice único si la columna existe
            if (Schema::hasColumn('products', 'sku')) {
                $table->unique('sku');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Eliminar el índice único si existe
            if (Schema::hasColumn('products', 'sku')) {
                $table->dropUnique(['sku']);
            }
        });
    }
};
