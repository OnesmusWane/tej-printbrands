<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PortfolioProjectFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->words(3, true);

        return [
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(100, 999),
            'title' => Str::title($title),
            'category' => fake()->randomElement(['Graphic Design', 'Printing', 'Signage', 'Branding']),
            'client' => fake()->company(),
            'project_date' => fake()->monthName().' '.fake()->year(),
            'image_url' => fake()->imageUrl(900, 650),
            'description' => fake()->paragraph(),
            'services' => fake()->words(3),
            'gallery' => [fake()->imageUrl(700, 450), fake()->imageUrl(700, 450)],
            'is_featured' => true,
            'is_visible' => true,
            'sort_order' => fake()->numberBetween(0, 30),
        ];
    }
}
