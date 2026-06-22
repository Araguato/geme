<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recurring_supplier_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('description');
            $table->decimal('base_amount_usd', 12, 2)->nullable();
            $table->decimal('base_amount_bs', 14, 2)->nullable();
            $table->string('currency', 10)->default('USD');
            $table->string('interval', 20); // monthly, yearly, weekly
            $table->unsignedTinyInteger('day_of_month')->nullable();
            $table->unsignedTinyInteger('day_of_week')->nullable(); // 1-7 (Mon-Sun)
            $table->date('next_due_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_supplier_invoices');
    }
};
