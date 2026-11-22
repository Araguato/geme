<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índices para los informes de ventas: búsqueda de órdenes por fecha/estado y del costo
 * de cada venta en el kardex.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index(['reference_type', 'reference_id', 'product_id'], 'stock_movements_reference_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'orders_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('stock_movements_reference_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_status_created_idx');
        });
    }
};
