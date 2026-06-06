<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SitePageFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->words(2, true);

        return ['slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(100, 999), 'title' => Str::title($title), 'subtitle' => fake()->sentence(), 'image_url' => fake()->imageUrl(), 'sort_order' => 0, 'is_published' => true];
    }
}
