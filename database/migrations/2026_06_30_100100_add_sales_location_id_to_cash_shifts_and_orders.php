<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_shifts', function (Blueprint $table) {
            $table->foreignId('sales_location_id')->nullable()->after('user_id')->constrained('sales_locations')->nullOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('sales_location_id')->nullable()->after('user_id')->constrained('sales_locations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cash_shifts', function (Blueprint $table) {
            $table->dropForeign(['sales_location_id']);
            $table->dropColumn('sales_location_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['sales_location_id']);
            $table->dropColumn('sales_location_id');
        });
    }
};
