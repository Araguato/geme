<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employment_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('contract_type', 50)->nullable();
            $table->string('job_title', 120)->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('salary_type', 20)->default('mensual');
            $table->decimal('salary_amount', 12, 2);
            $table->string('pay_frequency', 20)->default('mensual');
            $table->char('currency_code', 3)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'is_active']);
            $table->index(['salary_type', 'pay_frequency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employment_contracts');
    }
};
