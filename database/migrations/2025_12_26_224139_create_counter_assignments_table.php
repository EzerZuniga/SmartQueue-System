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
        Schema::create('counter_assignments', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('user_id')->constrained(); // El empleado
            $table->foreignId('counter_id')->constrained(); // La ventanilla

            // Tiempos
            $table->timestamp('opened_at'); // Cuando se sentó
            $table->timestamp('closed_at')->nullable(); // Cuando se fue (NULL = Actualmente ocupada)

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('counter_assignments');
    }
};
