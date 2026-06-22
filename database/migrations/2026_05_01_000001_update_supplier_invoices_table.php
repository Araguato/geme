<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_invoices', function (Blueprint $table) {
            $table->string('control_number')->nullable()->after('invoice_number');
            $table->string('currency_rate_source', 100)->nullable()->after('bcv_rate_at_issue');
            $table->decimal('total_items', 12, 2)->default(0)->after('amount_bs');
            $table->decimal('total_tax', 12, 2)->default(0)->after('total_items');
            $table->decimal('total_amount', 12, 2)->default(0)->after('total_tax');
            $table->string('doc_type', 10)->default('FC')->after('notes');
            $table->string('affected_document')->nullable()->after('doc_type');
            $table->decimal('taxable_amount', 12, 2)->default(0)->after('affected_document');
            $table->decimal('exempt_amount', 12, 2)->default(0)->after('taxable_amount');
            $table->decimal('withholding_amount', 12, 2)->default(0)->after('exempt_amount');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_invoices', function (Blueprint $table) {
            $table->dropColumn([
                'control_number',
                'currency_rate_source',
                'total_items',
                'total_tax',
                'total_amount',
                'doc_type',
                'affected_document',
                'taxable_amount',
                'exempt_amount',
                'withholding_amount',
            ]);
        });
    }
};
