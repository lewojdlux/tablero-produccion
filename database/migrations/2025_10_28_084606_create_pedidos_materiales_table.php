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

            $table->unsignedBigInteger('instalador_id');
            $table->foreign('instalador_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');

            $table->timestamp('fecha_solicitud')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');

            $table->timestamp('fecha_aprobacion')->nullable();
            $table->string('observaciones')->nullable();

            $table->timestamp('fecha_registro')->nullable();
            $table->unsignedBigInteger('ref_id_usuario_registro')->nullable();
            $table->foreign('ref_id_usuario_registro')->references('id')->on('users')->onDelete('restrict')->onUpdate('cascade');

            $table->timestamp('fecha_modificacion')->nullable();
            $table->unsignedBigInteger('ref_id_usuario_modificacion')->nullable();
            $table->foreign('ref_id_usuario_modificacion')->references('id')->on('users')->onDelete('restrict')->onUpdate('cascade');


            

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