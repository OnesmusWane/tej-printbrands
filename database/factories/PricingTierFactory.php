<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PricingTierFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->word();

        return [
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'name' => Str::title($name),
            'price' => 'From KES '.fake()->numberBetween(10000, 90000),
            'description' => fake()->sentence(),
            'features' => fake()->sentences(4),
            'color' => 'cyan',
            'is_popular' => fake()->boolean(20),
            'is_visible' => true,
            'orders_count' => fake()->numberBetween(0, 100),
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }
}
