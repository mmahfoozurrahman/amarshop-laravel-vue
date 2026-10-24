<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['phone' => '01700000000'],
            [
                'name' => 'Rahim Uddin',
                'email' => 'rahim@example.com',
                'password' => null,
                'role' => 'customer',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
    }
}
