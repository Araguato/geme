<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consentimiento de marketing en la ficha del cliente: sin él, el cliente no aparece en
 * exportaciones ni campañas. Se guarda cuándo y de dónde vino (TPV, registro web, ficha).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->boolean('marketing_opt_in')->default(false)->after('is_active');
            $table->timestamp('marketing_opt_in_at')->nullable()->after('marketing_opt_in');
            $table->string('marketing_opt_in_source', 50)->nullable()->after('marketing_opt_in_at');
            $table->index(['type', 'marketing_opt_in']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            $table->dropIndex(['type', 'marketing_opt_in']);
            $table->dropIndex(['email']);
            $table->dropColumn(['marketing_opt_in', 'marketing_opt_in_at', 'marketing_opt_in_source']);
        });
    }
};
