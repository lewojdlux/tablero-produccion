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
        Schema::create('solicitud_material', function (Blueprint $table) {
            $table->id('id_solicitud_material');

            $table->unsignedBigInteger('pedido_material_id');
            $table->foreign('pedido_material_id')->references('id_pedido_material')->on('pedidos_materiales')->onDelete('restrict')->onUpdate('cascade');

            $table->unsignedBigInteger('proveedor_id');
            $table->foreign('proveedor_id')->references('id_supplier')->on('suppliers')->onDelete('restrict')->onUpdate('cascade');

            $table->string('codigo_material');
            $table->string('descripcion_material');
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('total', 10, 2);

            $table->unsignedBigInteger('ref_id_usuario_registro')->nullable();
            $table->foreign('ref_id_usuario_registro')->references('id')->on('users')->onDelete('restrict')->onUpdate('cascade');
            $table->timestamp('fecha_registro')->nullable();


            $table->unsignedBigInteger('ref_id_usuario_modificacion')->nullable();
            $table->foreign('ref_id_usuario_modificacion')->references('id')->on('users')->onDelete('restrict')->onUpdate('cascade');
            $table->timestamp('fecha_modificacion')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitud_material');
    }
};