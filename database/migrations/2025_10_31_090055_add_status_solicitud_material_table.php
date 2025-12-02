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
            $table->enum('status', ['queued', 'in_progress', 'done'])->default('queued')->after('proveedor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('solicitud_material', function (Blueprint $table) {
            //
            $table->dropColumn('status');
        });
    }
};
