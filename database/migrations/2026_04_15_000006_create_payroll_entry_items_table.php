<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_entry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_concept_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['earning', 'deduction', 'contribution']);
            $table->decimal('quantity', 10, 4)->nullable();
            $table->decimal('rate', 12, 4)->nullable();
            $table->decimal('amount', 12, 2);
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_social_security_applicable')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_entry_items');
    }
};
