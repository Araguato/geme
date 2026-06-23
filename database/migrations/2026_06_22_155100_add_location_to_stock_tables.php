<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('warehouse_id')->constrained('locations')->nullOnDelete();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('warehouse_id')->constrained('locations')->nullOnDelete();
            $table->string('aisle', 20)->nullable()->after('location_id');
            $table->string('shelf', 20)->nullable()->after('aisle');
            $table->string('rack', 20)->nullable()->after('shelf');
            $table->string('bin', 20)->nullable()->after('rack');
            $table->string('section', 20)->nullable()->after('bin');
        });
    }

    public function down(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropColumn('location_id');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropColumn(['location_id', 'aisle', 'shelf', 'rack', 'bin', 'section']);
        });
    }
};
