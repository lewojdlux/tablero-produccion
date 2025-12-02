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
        Schema::create('detalle_solicitud_material', function (Blueprint $table) {
            $table->id('id_detalle_solicitud_material');

            $table->unsignedBigInteger('solicitud_material_id');
            $table->foreign('solicitud_material_id')->references('id_solicitud_material')->on('solicitud_material')->onDelete('cascade')->onUpdate('cascade');

            $table->string('codigo_material')->unique();
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('total', 10, 2);


            $table->timestamp('fecha_registro')->nullable();
            $table->unsignedBigInteger('user_reg')->nullable();
            $table->foreign('user_reg')->references('id')->on('users')->onDelete('restrict')->onUpdate('cascade');


            $table->timestamp('fecha_edit')->nullable();
            $table->unsignedBigInteger('user_edit')->nullable();
            $table->foreign('user_edit')->references('id')->on('users')->onDelete('restrict')->onUpdate('cascade');


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_solicitud_material');
    }
};
