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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained();

            // Datos del Turno
            $table->string('ticket_number')->index(); // Ej: A-001 (Indexado para búsquedas)
            $table->integer('number'); // 1
            $table->integer('position'); // Orden diario
            $table->integer('priority')->default(0)->index(); // 1=Preferencial. Indexado para ordenamiento.

            // Estado (Indexado porque se consulta cada segundo: "Dame los 'waiting'")
            // $table->string('status')->default('waiting')->index();
            $table->foreignId('call_status_id')->default(1)->constrained('call_statuses');

            // Datos Cliente
            $table->string('client_document')->required();
            $table->string('client_name')->nullable();
            $table->string('client_phone')->nullable();
            $table->string('client_email')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
