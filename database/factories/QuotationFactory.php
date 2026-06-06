<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class QuotationFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(10000, 150000);
        $tax = (int) round($subtotal * 0.16);

        return [
            'quote_number' => 'QT-'.fake()->unique()->numberBetween(1000, 9999),
            'client' => fake()->company(),
            'email' => fake()->companyEmail(),
            'service' => fake()->words(2, true),
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $subtotal + $tax,
            'status' => 'draft',
            'terms' => fake()->sentence(),
        ];
    }
}
