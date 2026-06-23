<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('cash_shift_id')->nullable()->constrained('cash_shifts')->nullOnDelete();
            $table->enum('method', ['cash', 'card', 'transfer', 'other'])->default('cash');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('reference')->nullable()->comment('Número de referencia para tarjeta/transferencia');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
