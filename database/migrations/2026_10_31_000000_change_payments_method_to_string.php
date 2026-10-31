<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // El enum original no incluía cashea ni pagomovil, y las ventas con esos métodos fallaban
        Schema::table('payments', function (Blueprint $table) {
            $table->string('method', 50)->default('cash')->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('method', ['cash', 'card', 'transfer', 'other'])->default('cash')->change();
        });
    }
};
