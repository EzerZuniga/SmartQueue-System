<?php

namespace Database\Factories;

use App\Models\Counter;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CounterAssignment>
 */
class CounterAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'counter_id' => Counter::factory(),
            'opened_at' => now(),
            'closed_at' => null, // Por defecto, la asignación está activa.
        ];
    }

    /**
     * Configura el factory para adjuntar un número específico de servicios.
     *
     * @param  int  $count  El número de servicios a crear y adjuntar.
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withServices(int $count = 1)
    {
        return $this->afterCreating(function ($assignment) use ($count) {
            $services = Service::factory()->count($count)->create();
            $assignment->services()->attach($services);
        });
    }

    /**
     * Indica que la asignación está cerrada.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function closed()
    {
        return $this->state(function (array $attributes) {
            return [
                'closed_at' => now(),
            ];
        });
    }
}
