<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ServiceFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->words(2, true);

        return [
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(100, 999),
            'title' => Str::title($title),
            'description' => fake()->paragraph(),
            'icon' => 'briefcase',
            'image_url' => fake()->imageUrl(800, 600),
            'starting_price' => 'KES '.fake()->numberBetween(5000, 50000),
            'features' => fake()->words(4),
            'sub_services' => fake()->words(3),
            'is_featured' => true,
            'is_visible' => true,
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }
}
