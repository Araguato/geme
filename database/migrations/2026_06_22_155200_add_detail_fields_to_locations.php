<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->string('aisle', 20)->nullable()->after('name');
            $table->string('shelf', 20)->nullable()->after('aisle');
            $table->string('rack', 20)->nullable()->after('shelf');
            $table->string('bin', 20)->nullable()->after('rack');
            $table->string('section', 20)->nullable()->after('bin');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['aisle', 'shelf', 'rack', 'bin', 'section']);
        });
    }
};
