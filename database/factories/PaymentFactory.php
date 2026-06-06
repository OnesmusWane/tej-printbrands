<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'payment_number' => 'PAY-'.fake()->unique()->numberBetween(1000, 9999),
            'client' => fake()->company(),
            'amount' => fake()->numberBetween(10000, 200000),
            'method' => fake()->randomElement(['Cash', 'M-Pesa', 'Bank Transfer']),
            'reference' => strtoupper(fake()->bothify('???#######')),
            'status' => 'completed',
            'paid_at' => now(),
        ];
    }
}
