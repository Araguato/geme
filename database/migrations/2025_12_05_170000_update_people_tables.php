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
        Schema::table('parties', function (Blueprint $table) {
            $table->string('type', 50)->default('supplier')->after('id');
            $table->string('name');
            $table->string('document_type', 10)->nullable();
            $table->string('document_number', 50)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('address', 500)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->string('contact_name')->nullable();
            $table->string('payment_terms', 50)->nullable();
            $table->string('default_currency', 10)->nullable();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('party_id')->constrained('parties')->cascadeOnDelete();
            $table->string('role', 100)->nullable();
            $table->date('hire_date')->nullable();
            $table->string('salary_type', 20)->nullable(); // mensual, por_hora, etc.
            $table->decimal('monthly_salary', 12, 2)->nullable();
            $table->decimal('hourly_rate', 12, 2)->nullable();
            $table->boolean('is_current')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'party_id',
                'role',
                'hire_date',
                'salary_type',
                'monthly_salary',
                'hourly_rate',
                'is_current',
            ]);
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn([
                'party_id',
                'contact_name',
                'payment_terms',
                'default_currency',
            ]);
        });

        Schema::table('parties', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'name',
                'document_type',
                'document_number',
                'phone',
                'email',
                'address',
                'notes',
                'is_active',
            ]);
        });
    }
};
