<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        $amount = fake()->numberBetween(10000, 200000);

        return [
            'invoice_number' => 'INV-'.fake()->unique()->numberBetween(1000, 9999),
            'client' => fake()->company(),
            'email' => fake()->companyEmail(),
            'amount' => $amount,
            'paid_amount' => 0,
            'status' => 'unpaid',
            'due_date' => now()->addDays(14),
            'payment_method' => 'Pending',
        ];
    }
}
