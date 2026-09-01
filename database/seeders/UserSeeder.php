<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password',
            'phone' => '09171234567',
        ]);

        User::factory()->create([
            'name' => 'Customer One',
            'email' => 'customer@example.com',
            'password' => 'password',
            'phone' => '09189876543',
        ]);

        User::factory()->count(13)->create();

        $this->command?->info('Users seeded. Admin: admin@example.com / password');
    }
}
