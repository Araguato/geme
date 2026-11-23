<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cola de emisión por medio digital autorizado (SNAT/2026/00093).
        Schema::create('fiscal_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 20)->default('FACTURA'); // FACTURA, NOTA_CREDITO, NOTA_DEBITO
            // pending, issued, error, contingency (talonario), cancelled
            $table->string('status', 20)->default('pending');
            $table->string('provider', 40)->nullable();
            $table->string('environment', 12)->default('test');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('payload')->nullable();
            $table->string('invoice_number')->nullable();
            $table->string('control_number')->nullable();
            $table->string('provider_reference')->nullable();
            $table->decimal('exchange_rate', 18, 6)->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['order_id', 'document_type']);
            $table->index(['status', 'next_attempt_at']);
        });

        // Tasa BCV aplicada en cada venta (equivalente en Bs exigido en el comprobante).
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('exchange_rate', 18, 6)->nullable()->after('total');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('exchange_rate');
        });
        Schema::dropIfExists('fiscal_documents');
    }
};
