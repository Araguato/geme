<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('product_id')->constrained('warehouses')->nullOnDelete();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('product_id')->constrained('warehouses')->nullOnDelete();
            $table->foreignId('to_warehouse_id')->nullable()->after('warehouse_id')->constrained('warehouses')->nullOnDelete()->comment('Para transferencias entre depósitos');
        });
    }

    public function down(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['to_warehouse_id']);
            $table->dropColumn(['warehouse_id', 'to_warehouse_id']);
        });
    }
};
