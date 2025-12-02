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
        Schema::create('pedidos_materiales_item', function (Blueprint $table) {
            $table->id('id_pedido_material_item');

            $table->unsignedBigInteger('pedido_material_id');
            $table->foreign('pedido_material_id')->references('id_pedido_material')->on('pedidos_materiales')->onDelete('cascade')->onUpdate('cascade');
            
            $table->string('codigo_material');
            $table->string('descripcion_material');
            $table->integer('cantidad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos_materiales_item');
    }
};