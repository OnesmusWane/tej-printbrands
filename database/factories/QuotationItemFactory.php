<?php

namespace Database\Factories;

use App\Models\Quotation;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuotationItemFactory extends Factory
{
    public function definition(): array
    {
        $qty = fake()->numberBetween(1, 5);
        $price = fake()->numberBetween(1000, 20000);

        return ['quotation_id' => Quotation::factory(), 'description' => fake()->sentence(3), 'quantity' => $qty, 'unit_price' => $price, 'total' => $qty * $price];
    }
}
