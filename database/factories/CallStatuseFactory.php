<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CallStatuse>
 */
class CallStatuseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->randomElement([
            'En espera',
            'Llamando',
            'En Atención',
            'Finalizado',
            'No Presentó',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name), // waiting, llamado, atendido, etc.
            'color' => '#808080',
            'is_final' => in_array($name, ['Atendido', 'No atendido']),
        ];
    }

    /**
     * Estado: En espera
     */
    public function waiting()
    {
        return $this->state(fn () => [
            'name' => 'En espera',
            'slug' => 'waiting',
            'color' => 'oklch(0.704 0.191 22.216)',
            'is_final' => false,
        ]);
    }

    /**
     * Estado: Llamando
     */
    public function calling()
    {
        return $this->state(fn () => [
            'name' => 'Llamando',
            'slug' => 'calling',
            'color' => 'oklch(0.85 0.20 85.00)', // amarillo
            'is_final' => false,
        ]);
    }

    /**
     * Estado: En progreso
     */
    public function inProgress()
    {
        return $this->state(fn () => [
            'name' => 'En Atención',
            'slug' => 'in_progress',
            'color' => 'oklch(0.488 0.243 264.376)', // Tu Azul Primary (Trabajando)
            'is_final' => false,
        ]);
    }

    /**
     * Estado: Completado
     */
    public function completed()
    {
        return $this->state(fn () => [
            'name' => 'Finalizado', // Equivalente a tu "Atendido"
            'slug' => 'completed',
            'color' => 'oklch(0.623 0.214 160.00)', // Verde (Éxito)
            'is_final' => true,
        ]);
    }

    /**
     * Estado: No Atendido
     */
    public function notShow()
    {
        return $this->state(fn () => [
            'name' => 'No Presentó', // Equivalente a tu "No Atendido"
            'slug' => 'no_show',
            'color' => 'oklch(0.50 0.08 300)', // // Violeta Grisáceo
            'is_final' => true,
        ]);
    }
}
