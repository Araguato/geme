<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->timestamp('terms_accepted_at')->nullable()->after('remember_token');
            $table->timestamp('privacy_accepted_at')->nullable()->after('terms_accepted_at');
            $table->boolean('marketing_opt_in')->default(false)->after('privacy_accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'terms_accepted_at', 'privacy_accepted_at', 'marketing_opt_in']);
        });
    }
};
