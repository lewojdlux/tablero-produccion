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
        Schema::create('production_status_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ref_id_production_order');
            $table->string('status', 32);
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['ref_id_production_order','status'], 'uniq_order_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_status_notifications');
    }
};