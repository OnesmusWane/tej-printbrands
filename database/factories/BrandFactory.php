<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BrandFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'logo_url' => null,
            'color' => 'text-blue-600',
            'domain' => fake()->domainName(),
            'industry' => fake()->word(),
            'is_visible' => true,
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }
}
