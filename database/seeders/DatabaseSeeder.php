<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        User::factory()->create([
            'name' => 'Operator Satu',
            'email' => 'operator@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Operator,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        User::factory()->create([
            'name' => 'Operator Dua',
            'email' => 'operator2@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Operator,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }
}
