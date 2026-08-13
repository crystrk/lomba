<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminPasswordResetSeeder extends Seeder
{
    public function run(): void
    {
        $plainPassword = now()->format('dmY');

        $adminUsers = User::where('role', UserRole::Admin)->get();

        if ($adminUsers->isEmpty()) {
            $admin = User::create([
                'name' => 'Admin',
                'email' => 'admin@email.com',
                'password' => Hash::make($plainPassword),
                'role' => UserRole::Admin,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $adminUsers = collect([$admin]);
        } else {
            foreach ($adminUsers as $admin) {
                $admin->update([
                    'password' => Hash::make($plainPassword),
                ]);
            }
        }

        if ($this->command) {
            $this->command->info('');
            $this->command->info('===================================================');
            $this->command->info('        RESET PASSWORD ADMIN BERHASIL              ');
            $this->command->info('===================================================');
            $this->command->info(' Format Password : ddmmyyyy (Tanggal Hari Ini)    ');
            $this->command->info(' Password Baru   : '.$plainPassword);
            $this->command->info('---------------------------------------------------');

            $tableData = $adminUsers->map(fn (User $user) => [
                'ID' => $user->id,
                'Nama' => $user->name,
                'Email' => $user->email,
                'Password Baru' => $plainPassword,
            ])->toArray();

            $this->command->table(['ID', 'Nama', 'Email', 'Password Baru'], $tableData);
            $this->command->info('===================================================');
            $this->command->info('');
        }
    }
}
