<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'request_number' => 'REQ-'.fake()->unique()->numberBetween(1000, 9999),
            'client' => fake()->company(),
            'email' => fake()->companyEmail(),
            'service' => fake()->randomElement(['Brand Identity Package', 'Marketing Collateral', 'Vehicle Wrap']),
            'budget' => 'KES '.fake()->numberBetween(25000, 200000),
            'timeline' => fake()->randomElement(['1-2 weeks', '2-3 weeks', '4-6 weeks']),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'status' => 'new',
            'description' => fake()->paragraph(),
        ];
    }
}
