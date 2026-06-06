<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProcessStepFactory extends Factory
{
    public function definition(): array
    {
        return ['number' => fake()->numerify('##'), 'title' => fake()->words(2, true), 'description' => fake()->sentence(), 'sort_order' => 0, 'is_visible' => true];
    }
}
