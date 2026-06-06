<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@tejprintbrands.com',
            'is_admin' => true,
        ]);

        User::factory()->create([
            'name' => 'Jane Customer',
            'email' => 'jane@example.com',
            'phone' => '0712345678',
        ]);

        $this->call(ReferenceContentSeeder::class);
    }
}
