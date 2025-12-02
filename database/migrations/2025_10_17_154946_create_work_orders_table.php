<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {

            $table->id('id_work_order');
            $table->integer('n_documento')->nullable();
            $table->integer('pedido')->nullable();
            $table->string('tercero')->nullable(); // cliente
            $table->string('vendedor')->nullable(); // asesor
            $table->integer('periodo')->nullable();
            $table->integer('ano')->nullable();
            $table->string('estado_factura')->nullable(); // FACTURADO / NO FACTURADO
            $table->integer('n_factura')->nullable();

            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
