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
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            // Relaciones (Snapshots)
            $table->foreignId('ticket_id')->constrained();
            $table->foreignId('service_id')->constrained(); // Indexado autom. por Laravel
            $table->foreignId('counter_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('call_status_id')->constrained();

            // Datos del Turno al momento de llamar
            $table->string('token_letter', 10)->nullable();
            $table->integer('token_number');

            // Tiempos (Fechas)
            $table->date('called_date')->index(); // CRUCIAL para reportes "por día"
            $table->timestamp('called_at')->useCurrent();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            // Duraciones en SEGUNDOS (Enteros para cálculo rápido)
            $table->integer('waiting_duration')->default(0);
            $table->integer('served_duration')->default(0);
            $table->integer('turn_around_duration')->default(0);

            $table->timestamps();
            $table->softDeletes(); // Por si hay que anular un registro erróneo
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
