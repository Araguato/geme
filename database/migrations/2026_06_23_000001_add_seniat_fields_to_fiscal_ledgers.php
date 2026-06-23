<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fiscal_ledgers', function (Blueprint $table) {
            $table->string('period', 7)->nullable()->after('entry_type')->comment('Periodo fiscal SENIAT YYYY-MM');
            $table->string('company_tax_id', 20)->nullable()->after('period')->comment('RIF del contribuyente');
            $table->string('company_name')->nullable()->after('company_tax_id');
            $table->decimal('tax_rate', 5, 2)->nullable()->after('tax_amount')->comment('Alicuota IVA %');
            $table->decimal('exonerated_amount', 18, 2)->default(0)->after('exempt_amount')->comment('Monto exonerado');
            $table->decimal('non_subject_amount', 18, 2)->default(0)->after('exonerated_amount')->comment('Monto no sujeto');
            $table->string('affected_document_number')->nullable()->after('control_number')->comment('Factura afectada para ND/NC');
            $table->boolean('is_exported')->default(false)->after('locked_at');

            $table->index(['period', 'entry_type']);
            $table->index('is_exported');
        });
    }

    public function down(): void
    {
        Schema::table('fiscal_ledgers', function (Blueprint $table) {
            $table->dropColumn([
                'period',
                'company_tax_id',
                'company_name',
                'tax_rate',
                'exonerated_amount',
                'non_subject_amount',
                'affected_document_number',
                'is_exported',
            ]);
        });
    }
};
