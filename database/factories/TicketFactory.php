<?php

namespace Database\Factories;

use App\Models\CallStatuse;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),

            // Datos del turno
            'ticket_number' => $this->faker->unique()->bothify('A-###'),
            'number' => $this->faker->numberBetween(1, 999),
            'position' => $this->faker->numberBetween(1, 100),
            'priority' => 0,

            // Estado del llamado (por defecto: waiting)
            'call_status_id' => CallStatuse::query()->where('slug', 'waiting')->value('id') ?? 1,

            // Datos del cliente
            'client_document' => '00000000',
            'client_name' => $this->faker->optional()->name(),
            'client_phone' => $this->faker->optional()->phoneNumber(),
            'client_email' => $this->faker->optional()->safeEmail(),
        ];
    }

    /**
     * Estado: ticket preferencial
     */
    public function priority(int $level = 1)
    {
        return $this->state(fn () => [
            'priority' => $level,
        ]);
    }

    /**
     * Estado: ticket en espera
     */
    public function waiting()
    {
        return $this->state(fn () => [
            'call_status_id' => CallStatuse::where('slug', 'waiting')->first()->id ?? 1,
        ]);
    }
}
