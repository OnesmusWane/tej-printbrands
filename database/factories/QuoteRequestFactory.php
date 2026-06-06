<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class QuoteRequestFactory extends Factory
{
    public function definition(): array
    {
        return ['request_number' => 'QR-'.fake()->unique()->numberBetween(1000, 9999), 'name' => fake()->name(), 'email' => fake()->safeEmail(), 'phone' => fake()->phoneNumber(), 'product' => 'Business Cards', 'quantity' => '251-1000', 'size' => 'standard', 'delivery_method' => 'pickup', 'notes' => fake()->paragraph(), 'status' => 'new'];
    }
}
