<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductOptionFactory extends Factory
{
    public function definition(): array
    {
        return ['product_id' => Product::factory(), 'name' => 'Size', 'choices' => ['S', 'M', 'L'], 'sort_order' => 0];
    }
}
