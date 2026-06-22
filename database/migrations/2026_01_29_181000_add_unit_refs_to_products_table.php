<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('stock_unit_id')->nullable()->after('is_stock_tracked')->constrained('units');
            $table->foreignId('base_unit_id')->nullable()->after('stock_unit_id')->constrained('units');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['stock_unit_id']);
            $table->dropForeign(['base_unit_id']);
            $table->dropColumn(['stock_unit_id', 'base_unit_id']);
        });
    }
};
