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
        Schema::create('instalador', function (Blueprint $table) {
            $table->id('id_instalador');
            $table->string('nombre_instalador');
            $table->string('celular_instalador')->nullable();
            $table->string('email_instalador')->nullable();
            $table->string('username')->unique();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instalador');
    }
};
