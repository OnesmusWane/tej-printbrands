<?php

namespace Database\Factories;

use App\Models\SitePage;
use Illuminate\Database\Eloquent\Factories\Factory;

class SiteSectionFactory extends Factory
{
    public function definition(): array
    {
        return ['site_page_id' => SitePage::factory(), 'key' => fake()->unique()->slug(2), 'label' => fake()->word(), 'heading' => fake()->sentence(3), 'subtext' => fake()->paragraph(), 'image_url' => fake()->imageUrl(), 'settings' => [], 'sort_order' => 0, 'is_published' => true];
    }
}
