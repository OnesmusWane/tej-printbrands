<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class FaqFactory extends Factory
{
    public function definition(): array
    {
        return ['question' => fake()->sentence().'?', 'answer' => fake()->paragraph(), 'page_slug' => 'contact', 'is_visible' => true, 'sort_order' => 0];
    }
}
