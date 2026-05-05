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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('prefix'); // Ej: "A"
            $table->integer('start_number')->default(1);
            $table->boolean('status')->default(true)->index(); // Índice para filtrar activos rápido

            // Reglas de Kiosco (Input del cliente)
            $table->boolean('ask_document')->default(true)->index();
            // $table->boolean('ask_name')->default(false);
            // $table->boolean('name_required')->default(false);
            // $table->boolean('ask_email')->default(false);
            // $table->boolean('ask_phone')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
