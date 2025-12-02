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
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id('id_production_order');
            $table->string('ticket_code')->unique(); // ej: 250828-0007


            $table->integer('tipo_transaccion')->nullable();
            $table->integer('n_documento')->nullable();
            $table->integer('pedido')->nullable();
            $table->string('tercero')->nullable(); // cliente
            $table->string('vendedor')->nullable(); // asesor
            $table->string('luminaria')->nullable(); // StrProducto
            $table->text('observaciones')->nullable();
            $table->integer('periodo')->nullable();
            $table->integer('ano')->nullable();
            $table->dateTime('fecha_orden_produccion')->nullable();
            $table->string('estado_factura')->nullable(); // FACTURADO / NO FACTURADO
            $table->integer('n_factura')->nullable();


            $table->enum('status', ['queued','in_progress','done','approved'])->default('queued'); // estados generales

            $table->timestamp('queued_at')->nullable(); // en cola
            $table->timestamp('started_at')->nullable(); // en progreso
            $table->timestamp('paused_at')->nullable(); // en pausa
            $table->unsignedInteger('paused_accumulated_min')->default(0); // en pausa
            $table->timestamp('finished_at')->nullable(); // terminado
            $table->timestamp('approved_at')->nullable(); // aprobado


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_orders');
    }
};
