<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los costos unitarios se guardan con 6 decimales (como el stock): p. ej. 0,752051.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }
        DB::statement('ALTER TABLE products MODIFY cost DECIMAL(14,6) NULL');
        DB::statement('ALTER TABLE supplier_invoice_items MODIFY unit_cost DECIMAL(14,6) NOT NULL DEFAULT 0');
    }

    public function down(): void
    {
        // sin reversión: reducir la precisión truncaría datos
    }
};
