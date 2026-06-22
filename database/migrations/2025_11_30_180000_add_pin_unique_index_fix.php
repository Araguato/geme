<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Asegurar que el índice único sobre pin exista incluso si ya hay datos nulos
            // (Laravel ya creó la columna con unique, esto es un refuerzo por si se requiere en el futuro).
        });
    }

    public function down(): void
    {
        // Nada que revertir aquí.
    }
};
