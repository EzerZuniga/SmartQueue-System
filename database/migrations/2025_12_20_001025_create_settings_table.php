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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            // Datos de la Organización
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('location')->nullable();

            // Apariencia y UI
            $table->string('logo_path')->nullable();
            $table->string('footer_text')->default('Todos los derechos reservados');
            $table->string('theme_color')->default('#000000'); // Color principal

            // Pantalla de Turnos (TV)
            $table->text('display_notification')->nullable(); // Marquee text
            $table->integer('display_font_size')->default(24);
            $table->string('display_font_color')->default('#000000');

            // Funcionalidades
            $table->boolean('print_preview_enabled')->default(true);
            $table->boolean('voice_enabled')->default(true); // TTS

            // Token de acceso para el Kiosko
            $table->string('kiosk_token')->nullable();

            // Codigo para el kiosko
            $table->string('kiosk_code')->required()->default('1234');

            $table->integer('ticket_cooldown_minutes')->default(10);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
