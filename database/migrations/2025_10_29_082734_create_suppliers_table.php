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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id('id_supplier');
            $table->string('code_supplier', 100)->unique();
            $table->string('name_supplier', 255);
            $table->enum('status', ['active', 'inactive'])->default('active');


            
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
        Schema::dropIfExists('suppliers');
    }
};