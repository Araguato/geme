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
            $table->foreignId('warehouse_id')->nullable()->after('category_id')->constrained('warehouses')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->after('warehouse_id')->constrained('locations')->nullOnDelete();
            $table->string('aisle', 50)->nullable()->after('location_id')->comment('Pasillo');
            $table->string('shelf', 50)->nullable()->after('aisle')->comment('Estante');
            $table->string('rack', 50)->nullable()->after('shelf')->comment('Anaquel');
            $table->string('bin', 50)->nullable()->after('rack')->comment('Cajón / casillero');
            $table->string('section', 50)->nullable()->after('bin')->comment('Vitrina / cubículo / sección');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['location_id']);
            $table->dropColumn([
                'warehouse_id',
                'location_id',
                'aisle',
                'shelf',
                'rack',
                'bin',
                'section',
            ]);
        });
    }
};
