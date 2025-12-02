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
        Schema::table('solicitud_material', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('instalador_id')->after('pedido_material_id');
            $table->foreign('instalador_id')->references('id_instalador')->on('instalador')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitud_material', function (Blueprint $table) {
            //
            $table->dropForeign(['instalador_id']);
            $table->dropColumn('instalador_id');
        });
    }
};
