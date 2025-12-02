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
        //
        Schema::create('sequences', function (Blueprint $table) {
            $table->id('id_sequence');
            $table->string('name_sequence', 100);
            $table->integer('initial_value');
            $table->integer('current_value');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists('sequences');
    }
};
