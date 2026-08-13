<?php

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\AdminPasswordResetSeeder;
use Illuminate\Support\Facades\Hash;

test('admin password reset seeder resets admin password to ddmmyyyy format', function () {
    $admin = User::factory()->create([
        'email' => 'admin@email.com',
        'password' => Hash::make('oldpassword'),
        'role' => UserRole::Admin,
    ]);

    $expectedPassword = now()->format('dmY');

    $this->seed(AdminPasswordResetSeeder::class);

    $admin->refresh();

    expect(Hash::check($expectedPassword, $admin->password))->toBeTrue();
});
