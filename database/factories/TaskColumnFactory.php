<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TaskColumnFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->words(2, true);

        return ['slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(100, 999), 'title' => Str::title($title), 'color' => 'bg-gray-200 text-gray-700', 'sort_order' => 0];
    }
}
