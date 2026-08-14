<?php

namespace Database\Factories;

use App\Models\DonorEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DonorEvent>
 */
class DonorEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'organizer' => fake()->company(),
            'description' => fake()->paragraph(),
            'location_name' => fake()->city(),
            'latitude' => fake()->latitude(-6.5, -5.5),
            'longitude' => fake()->longitude(106.5, 107.5),
            'event_date' => fake()->dateTimeBetween('+1 day', '+30 days'),
            'quota' => fake()->numberBetween(10, 100),
        ];
    }
}
