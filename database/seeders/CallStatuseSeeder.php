<?php

namespace Database\Seeders;

use App\Models\CallStatuse;
use Illuminate\Database\Seeder;

class CallStatuseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            [
                'name' => 'En Espera',
                'slug' => 'waiting',
                'color' => 'oklch(0.50 0.08 300)', // Naranja/Rojo suave (Pendiente)
                'is_final' => false,
            ],
            [
                'name' => 'Llamando',
                'slug' => 'calling',
                'color' => 'oklch(0.85 0.20 85.00)', // Amarillo/Ambar (Alerta)
                'is_final' => false,
            ],
            [
                'name' => 'En Atención',
                'slug' => 'in_progress',
                'color' => 'oklch(0.488 0.243 264.376)', // Tu Azul Primary (Trabajando)
                'is_final' => false,
            ],
            [
                'name' => 'Finalizado', // Equivalente a tu "Atendido"
                'slug' => 'completed',
                'color' => 'oklch(0.623 0.214 160.00)', // Verde (Éxito)
                'is_final' => true,
            ],
            [
                'name' => 'No Presentó', // Equivalente a tu "No Atendido"
                'slug' => 'no_show',
                'color' => 'oklch(0.704 0.191 22.216)', // // Violeta Grisáceo
                'is_final' => true,
            ],
            [
                'name' => 'Derivado',
                'slug' => 'transferred',
                'color' => 'oklch(0.70 0.12 250)', // Celeste/Turquesa
                'is_final' => true,
            ],
        ];

        foreach ($statuses as $status) {
            CallStatuse::create($status);
        }
    }
}
