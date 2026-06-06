<?php

namespace Database\Factories;

use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'product_category_id' => ProductCategory::factory(),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'name' => Str::title($name),
            'price' => fake()->numberBetween(500, 50000),
            'unit' => 'each',
            'description' => fake()->paragraph(),
            'image_url' => fake()->imageUrl(800, 600),
            'rating' => fake()->randomFloat(1, 4, 5),
            'features' => fake()->sentences(3),
            'is_visible' => true,
            'sort_order' => 0,
        ];
    }
}
