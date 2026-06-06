<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ContactMessageFactory extends Factory
{
    public function definition(): array
    {
        $first = fake()->firstName();
        $last = fake()->lastName();

        return [
            'first_name' => $first,
            'last_name' => $last,
            'name' => "{$first} {$last}",
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'subject' => fake()->randomElement(['General Inquiry', 'Quote Request', 'Service Booking']),
            'message' => fake()->paragraph(),
            'status' => 'new',
            'is_starred' => false,
        ];
    }
}
