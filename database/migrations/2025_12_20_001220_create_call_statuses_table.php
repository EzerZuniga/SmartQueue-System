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
        Schema::create('call_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ej: Espera, Llamado, Atendido, No Atendido
            $table->string('slug')->unique(); // waiting, called, served
            $table->string('color')->default('#808080'); // Para Vue/Inertia
            $table->boolean('is_final')->default(false); // Ayuda a filtrar tickets "abiertos" vs "cerrados" rápidamente
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_statuses');
    }
};
