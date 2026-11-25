<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * tax_rate era decimal(5,4) (máx. 9,9999): el IVA se guarda en % (16 = 16 %)
     * y MySQL rechazaba el valor con error 500.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }
        Schema::getConnection()->statement('ALTER TABLE supplier_invoice_items MODIFY tax_rate DECIMAL(7,4) NOT NULL DEFAULT 0');
    }

    public function down(): void
    {
        // sin reversión: reducir la precisión podría truncar datos
    }
};
