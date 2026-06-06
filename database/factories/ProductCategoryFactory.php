<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductCategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->words(2, true);

        return ['slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999), 'name' => Str::title($name), 'sort_order' => 0];
    }
}
