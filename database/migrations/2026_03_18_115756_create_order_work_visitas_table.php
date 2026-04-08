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
        Schema::create('order_work_visitas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_work_id');
            $table->date('fecha_visita');
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->foreign('order_work_id')
                ->references('id_work_order')
                ->on('work_orders')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_work_visitas');
    }
};
