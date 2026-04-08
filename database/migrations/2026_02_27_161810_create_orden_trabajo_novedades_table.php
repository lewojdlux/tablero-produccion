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
        Schema::create('orden_trabajo_novedades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('orden_trabajo_id');
        $table->date('fecha_afectada');

        $table->string('tipo_novedad', 50);
        // cliente_no_disponible | clima | material_pendiente | otro

        $table->text('observacion')->nullable();

        $table->boolean('reprogramar')->default(false);
        $table->date('nueva_fecha')->nullable();

        $table->unsignedBigInteger('user_id');

        $table->timestamps();

        $table->foreign('orden_trabajo_id')
            ->references('id_work_order')
            ->on('work_orders')
            ->onDelete('cascade');

        $table->foreign('user_id')
            ->references('id')
            ->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orden_trabajo_novedades');
    }
};
