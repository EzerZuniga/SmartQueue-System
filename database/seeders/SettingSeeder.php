<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Usamos updateOrCreate buscando por ID = 1.
        // Si existe, lo actualiza (opcional); si no, lo crea.
        // Esto garantiza que solo haya una fila de configuración.

        Setting::updateOrCreate(
            ['id' => 1],
            [
                // Datos de la Organización
                'name' => 'Centro de Atención Principal',
                'address' => 'Av. Principal 123, Ciudad Capital',
                'email' => 'contacto@miempresa.com',
                'phone' => '+51 987 654 321',
                'location' => 'Sede Central',

                // Apariencia y UI
                'logo_path' => null, // null por defecto hasta que suban uno
                'footer_text' => '© '.date('Y').' Todos los derechos reservados',
                'theme_color' => '#2563EB', // Un azul estándar (Tailwind blue-600)

                // Pantalla de Turnos (Kiosco)
                'display_notification' => 'Bienvenido a nuestro centro de atención. Por favor tome asiento y espere su llamado.',
                'display_font_size' => 28,
                'display_font_color' => '#1f2937', // Gris oscuro (casi negro)

                // Funcionalidades
                'print_preview_enabled' => true,
                'voice_enabled' => true,

                'kiosk_token' => \Illuminate\Support\Str::random(32),
            ]
        );
    }
}
