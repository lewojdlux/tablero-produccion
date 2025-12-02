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
        Schema::create('work_orders_materials', function (Blueprint $table) {
            $table->id('id_work_order_material');

            $table->unsignedBigInteger('work_order_id');
            $table->foreign('work_order_id')->references('id_work_order')->on('work_orders')->onDelete('cascade')->onUpdate('cascade');

            $table->unsignedBigInteger('material_id');
            $table->foreign('material_id')->references('id_material')->on('materiales')->onDelete('cascade')->onUpdate('cascade');

            $table->integer('cantidad');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_orders_materials');
    }
};
