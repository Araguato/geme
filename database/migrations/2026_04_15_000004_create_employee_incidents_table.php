<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_period_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payroll_concept_id')->nullable()->constrained()->nullOnDelete();
            $table->date('incident_date');
            $table->string('incident_type', 50);
            $table->decimal('quantity', 10, 4)->nullable();
            $table->decimal('hours', 8, 2)->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'incident_date']);
            $table->index(['status', 'incident_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_incidents');
    }
};
