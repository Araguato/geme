<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Contingencia: las facturas de talonario se remiten al día siguiente; aquí se anota cuándo y quién. */
    public function up(): void
    {
        Schema::table('fiscal_documents', function (Blueprint $table) {
            $table->timestamp('reported_at')->nullable();
            $table->unsignedBigInteger('reported_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('fiscal_documents', fn (Blueprint $t) => $t->dropColumn(['reported_at', 'reported_by']));
    }
};
