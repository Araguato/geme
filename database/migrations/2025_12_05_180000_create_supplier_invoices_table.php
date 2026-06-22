<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('invoice_number')->nullable();
            $table->date('date');
            $table->date('due_date')->nullable();
            $table->decimal('bcv_rate_at_issue', 12, 6)->default(0);
            $table->decimal('amount_usd', 12, 2); // monto base en USD
            $table->decimal('amount_bs', 14, 2)->nullable(); // monto original en Bs si aplica
            $table->string('currency', 10)->default('USD');
            $table->string('status', 20)->default('pendiente'); // pendiente, parcial, pagada
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_invoices');
    }
};
