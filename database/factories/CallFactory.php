<?php

namespace Database\Factories;

use App\Models\CallStatuse;
use App\Models\Counter;
use App\Models\Service;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Call>
 */
class CallFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'service_id' => Service::factory(),
            'counter_id' => Counter::factory(),
            'user_id' => User::factory(),
            'call_status_id' => CallStatuse::factory(),
            'token_letter' => $this->faker->randomLetter(),
            'token_number' => $this->faker->numberBetween(1, 999),
            'called_date' => $this->faker->date(),
            'called_at' => $this->faker->dateTime(),
            'started_at' => $this->faker->optional()->dateTime(),
            'ended_at' => $this->faker->optional()->dateTime(),
            'waiting_duration' => 0,
            'served_duration' => 0,
            'turn_around_duration' => 0,
        ];
    }
}
