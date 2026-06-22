<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_stock_tracked')->default(false)->after('is_taxable');
            $table->boolean('is_prepared')->default(false)->after('is_stock_tracked');
            $table->boolean('is_raw_material')->default(false)->after('is_prepared');
            $table->string('default_unit', 20)->nullable()->after('is_raw_material');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_stock_tracked', 'is_prepared', 'is_raw_material', 'default_unit']);
        });
    }
};
