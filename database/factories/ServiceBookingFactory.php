<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceBookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_number' => 'BKG-'.fake()->unique()->numberBetween(1000, 9999),
            'client' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'service' => fake()->randomElement(['Graphic Design', 'Printing', 'Signage']),
            'preferred_date' => fake()->dateTimeBetween('+1 day', '+1 month'),
            'preferred_time' => '10:00',
            'duration' => '1 hour',
            'location' => 'Video Call',
            'budget' => 'KES 50,000 - 100,000',
            'project_description' => fake()->paragraph(),
            'status' => 'pending',
        ];
    }
}
