<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_product_id')->nullable()->after('category_id');
            $table->json('variant_attributes')->nullable()->after('parent_product_id');
            $table->index('parent_product_id');
            $table->foreign('parent_product_id')->references('id')->on('products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['parent_product_id']);
            $table->dropIndex(['parent_product_id']);
            $table->dropColumn(['parent_product_id', 'variant_attributes']);
        });
    }
};
