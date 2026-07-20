<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MakeAdmin extends Command
{
    protected $signature = 'app:make-admin {--email= : Email for the admin user}';

    protected $description = 'Create the first admin user or promote an existing user to admin';

    public function handle(): int
    {
        $email = $this->option('email');

        if (! $email) {
            $email = $this->ask('Email for admin user');
        }

        $existing = User::where('email', $email)->first();

        if ($existing) {
            if ($existing->role === UserRole::Admin) {
                $this->warn("User with email {$email} is already an admin.");

                return self::SUCCESS;
            }

            $existing->update([
                'role' => UserRole::Admin,
                'is_active' => true,
                'email_verified_at' => $existing->email_verified_at ?? now(),
            ]);

            $this->info("User {$email} has been promoted to admin.");

            return self::SUCCESS;
        }

        $name = $this->ask('Name for admin user', 'Admin');
        $password = $this->secret('Password for admin user (minimum 8 characters)');

        if (strlen($password) < 8) {
            throw ValidationException::withMessages(['password' => 'Password must be at least 8 characters.']);
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => UserRole::Admin,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->info("Admin user {$email} has been created successfully.");

        return self::SUCCESS;
    }
}
