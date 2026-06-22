<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_ledgers', function (Blueprint $table) {
            $table->id();
            $table->enum('entry_type', ['entrada', 'salida']);
            $table->date('document_date');
            $table->string('document_type')->nullable();
            $table->string('document_number')->nullable();
            $table->string('control_number')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('partner_name')->nullable();
            $table->string('partner_tax_id')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->string('related_type')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 18, 6)->nullable();
            $table->decimal('taxable_amount', 18, 2)->default(0);
            $table->decimal('exempt_amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->decimal('withholding_amount', 18, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->string('fiscal_hash', 255)->nullable();
            $table->string('previous_hash', 255)->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->index(['document_date', 'entry_type']);
            $table->index(['related_type', 'related_id']);
            $table->index('partner_tax_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_ledgers');
    }
};
