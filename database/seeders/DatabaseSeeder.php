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
        User::firstOrCreate(
            ['email' => 'admin@m2b.co.id'],
            [
                'name' => 'Admin M2B',
                'password' => bcrypt('password'),
                'role' => User::ROLE_ADMIN,
                'email_verified_at' => now(),
            ],
        );
    }
}
