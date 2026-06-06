<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'onesmuswane@gmail.com'],
            [
                'name'       => 'Onesmus Wane',
                'password'   => bcrypt('Admin@1234'),
                'is_admin'   => true,
                'role'       => 'super_admin',
                'permissions' => null,
            ]
        );

        $this->command->info('Super admin created: onesmuswane@gmail.com / Admin@1234');
        $this->command->warn('Change the password immediately after first login.');
    }
}
