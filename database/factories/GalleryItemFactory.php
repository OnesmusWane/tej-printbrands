<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class GalleryItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true),
            'category' => fake()->randomElement(['Branding', 'Printing', 'Graphic Design', 'Signage']),
            'image_url' => fake()->imageUrl(800, 600),
            'span' => '',
            'file_size' => fake()->randomFloat(1, 1, 5).' MB',
            'uploaded_at' => now(),
            'is_visible' => true,
            'sort_order' => fake()->numberBetween(0, 30),
        ];
    }
}
