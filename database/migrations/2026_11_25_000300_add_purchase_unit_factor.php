<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Se compra en una unidad (p. ej. kilo) y se vende en otra (pieza): el renglón guarda cuántas
     * piezas trae cada unidad comprada y el producto recuerda su equivalencia habitual.
     */
    public function up(): void
    {
        Schema::table('supplier_invoice_items', function (Blueprint $table) {
            $table->decimal('unit_factor', 14, 6)->default(1)->after('unit');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('purchase_unit', 20)->nullable();
            $table->decimal('purchase_unit_factor', 14, 6)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('supplier_invoice_items', fn (Blueprint $t) => $t->dropColumn('unit_factor'));
        Schema::table('products', fn (Blueprint $t) => $t->dropColumn(['purchase_unit', 'purchase_unit_factor']));
    }
};
