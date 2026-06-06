<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TestimonialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'role' => fake()->jobTitle(),
            'text' => fake()->paragraph(),
            'rating' => fake()->numberBetween(3, 5),
            'status' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'submitted_at' => now(),
        ];
    }
}
