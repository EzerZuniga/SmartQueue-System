<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'prefix' => strtoupper($this->faker->lexify('??')), // Ej: AB
            'start_number' => 1,
            'status' => true,
            'ask_name' => $this->faker->boolean,
            'name_required' => false,
            'ask_email' => false,
            'ask_phone' => false,
        ];
    }
}
