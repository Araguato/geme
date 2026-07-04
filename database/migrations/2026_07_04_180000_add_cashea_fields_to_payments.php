<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('cashea_initial_percentage', 5, 2)->nullable()->after('amount');
            $table->decimal('cashea_financed_amount', 12, 2)->nullable()->after('cashea_initial_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['cashea_initial_percentage', 'cashea_financed_amount']);
        });
    }
};
