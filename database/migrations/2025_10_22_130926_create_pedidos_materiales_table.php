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
        Schema::create('pedidos_materiales', function (Blueprint $table) {
            $table->id('id_pedido_material');

            $table->unsignedBigInteger('orden_trabajo_id');
            $table->foreign('orden_trabajo_id')->references('id_work_order')->on('work_orders')->onDelete('cascade')->onUpdate('cascade');

            $table->unsignedBigInteger('material_id');
            $table->foreign('material_id')->references('id_material')->on('materiales')->onDelete('cascade')->onUpdate('cascade');

            $table->integer('cantidad');
            $table->decimal('precio_unitario', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos_materiales');
    }
};