<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('customer_party_id')->nullable()->constrained('parties')->nullOnDelete()->after('customer_phone')->comment('Cliente para Libro de Ventas SENIAT');
            $table->string('document_type', 20)->nullable()->after('customer_party_id')->comment('FACTURA, NOTA_DEBITO, NOTA_CREDITO');
            $table->string('external_invoice_number')->nullable()->after('document_type')->comment('Número de factura fiscal');
            $table->string('affected_document_number')->nullable()->after('external_invoice_number')->comment('Número de factura afectada por ND/NC');

            $table->index('customer_party_id');
            $table->index('document_type');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['customer_party_id']);
            $table->dropColumn([
                'customer_party_id',
                'document_type',
                'external_invoice_number',
                'affected_document_number',
            ]);
        });
    }
};
