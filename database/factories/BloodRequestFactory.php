<?php

namespace Database\Factories;

use App\Models\BloodRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BloodRequest>
 */
class BloodRequestFactory extends Factory
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
            'patient_name' => fake()->name(),
            'blood_type' => fake()->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            'bags_needed' => fake()->numberBetween(1, 5),
            'bags_fulfilled' => 0,
            'hospital_name' => fake()->company(),
            'latitude' => fake()->latitude(-6.5, -5.5),
            'longitude' => fake()->longitude(106.5, 107.5),
            'urgency_level' => fake()->randomElement(['normal', 'urgent', 'critical']),
            'medical_reference_proof' => null,
            'status' => 'open',
        ];
    }
}
