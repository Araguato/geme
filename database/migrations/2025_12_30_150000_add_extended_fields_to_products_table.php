<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Precios y costos
            $table->decimal('cost', 10, 2)->nullable()->after('price');
            $table->decimal('markup_percent', 5, 2)->nullable()->after('cost');
            $table->decimal('tax_rate', 5, 2)->default(0)->after('markup_percent');
            $table->boolean('is_tax_inclusive')->default(true)->after('tax_rate');

            // Stock (a nivel de producto)
            $table->decimal('stock_quantity', 15, 3)->default(0)->after('is_stock_tracked');
            $table->decimal('reorder_point', 15, 3)->default(0)->after('stock_quantity');
            $table->decimal('preferred_quantity', 15, 3)->default(0)->after('reorder_point');
            $table->decimal('warning_quantity', 15, 3)->default(0)->after('preferred_quantity');

            // Otros
            $table->string('measurement_unit', 50)->nullable()->after('default_unit');
            $table->string('supplier_name', 255)->nullable()->after('measurement_unit');
            $table->boolean('is_service')->default(false)->after('is_raw_material');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'cost',
                'markup_percent',
                'tax_rate',
                'is_tax_inclusive',
                'stock_quantity',
                'reorder_point',
                'preferred_quantity',
                'warning_quantity',
                'measurement_unit',
                'supplier_name',
                'is_service',
            ]);
        });
    }
};
