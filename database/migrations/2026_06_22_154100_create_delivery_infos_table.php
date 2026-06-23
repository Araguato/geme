<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_infos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->nullable()->comment('Relación lógica con orders; FK agregada posteriormente');
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('phone')->nullable();
            $table->text('instructions')->nullable();
            $table->enum('status', ['pending', 'preparing', 'in_transit', 'delivered', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_infos');
    }
};
