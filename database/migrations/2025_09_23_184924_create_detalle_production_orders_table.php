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
        Schema::create('detalle_production_orders', function (Blueprint $table) {
            $table->id('id_detalle_production_order');
            $table->unsignedBigInteger('ref_id_production_order');
            $table->foreign('ref_id_production_order')->references('id_production_order')->on('production_orders')->onDelete('restrict')->onUpdate('cascade');
            $table->date('fecha_inicial_produccion')->nullable();
            $table->date('fecha_final_produccion')->nullable();
            $table->integer('dias_produccion')->nullable();

            $table->time('hora_inicio_produccion')->nullable();
            $table->time('hora_fin_produccion')->nullable();
            $table->integer('horas_produccion')->nullable();
            $table->integer('minutos_produccion')->nullable();
            $table->integer('segundos_produccion')->nullable();
            $table->integer('cantidad_luminarias')->nullable();

            $table->timestamp('fecha_registro');
            $table->unsignedBigInteger('ref_id_usuario_registro')->nullable();
            $table->foreign('ref_id_usuario_registro')->references('id')->on('users')->onDelete('restrict')->onUpdate('cascade');

            $table->timestamp('fecha_actualizacion')->nullable();
            $table->unsignedBigInteger('ref_id_usuario_actualizacion')->nullable();
            $table->foreign('ref_id_usuario_actualizacion')->references('id')->on('users')->onDelete('restrict')->onUpdate('cascade');


            $table->timestamp('fecha_estado')->nullable();
            $table->unsignedBigInteger('ref_id_usuario_estado')->nullable();
            $table->foreign('ref_id_usuario_estado')->references('id')->on('users')->onDelete('restrict')->onUpdate('cascade');

            $table->text('observacion_produccion')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_production_orders');
    }
};