<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Nota de entrega: venta sin factura todavía. Se factura después (invoiced_at).
            $table->boolean('is_delivery_note')->default(false)->index();
            $table->string('delivery_note_number')->nullable()->unique();
            $table->timestamp('invoiced_at')->nullable();
            $table->foreignId('invoiced_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invoiced_by');
            $table->dropUnique(['delivery_note_number']);
            $table->dropIndex(['is_delivery_note']);
            $table->dropColumn(['is_delivery_note', 'delivery_note_number', 'invoiced_at']);
        });
    }
};
