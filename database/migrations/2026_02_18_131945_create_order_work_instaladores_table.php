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
        Schema::create('order_work_instaladores', function (Blueprint $table) {
            $table->id('id_order_work_instaladores');
            $table->unsignedBigInteger('order_work_id');
            $table->unsignedBigInteger('instalador_id');

            $table->timestamps();

            $table->foreign('order_work_id')
                ->references('id_work_order')
                ->on('work_orders')
                ->onDelete('cascade');

            $table->foreign('instalador_id')
                ->references('id_instalador')
                ->on('instalador')
                ->onDelete('cascade');

            $table->unique(['order_work_id','instalador_id']);

            


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_work_instaladores');
    }
};