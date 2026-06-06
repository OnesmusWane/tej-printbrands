<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerAddressFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'label' => 'Default', 'recipient_name' => fake()->name(), 'phone' => fake()->phoneNumber(), 'address_line_1' => fake()->streetAddress(), 'address_line_2' => null, 'city' => fake()->city(), 'county' => 'Nairobi', 'country' => 'Kenya', 'is_default' => true];
    }
}
