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
        Schema::create('detalle_material_proveedor', function (Blueprint $table) {
            $table->id('id_detalle_proveedor');
            $table->unsignedBigInteger('solicitud_material_id');
            $table->string('codigo_material', 100)->nullable();
            $table->string('nombre_material', 255)->nullable(); // descripción o nombre
            $table->integer('cantidad')->default(0);
            $table->decimal('precio_unitario', 12, 2)->nullable();
            $table->decimal('total', 14, 2)->nullable();
            $table->string('proveedor')->nullable();
            $table->timestamp('fecha_registro')->nullable();
            $table->string('user_reg')->nullable();
            $table->timestamps();

            $table->foreign('solicitud_material_id')
                ->references('id_solicitud_material')
                ->on('solicitud_material')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_material_proveedor');
    }
};