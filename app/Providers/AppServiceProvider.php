<?php

namespace App\Providers;

use App\Enums\UserRole;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::define('admin-access', fn ($user) => $user->role === UserRole::Admin);
        Gate::define('operator-access', fn ($user) => $user->role === UserRole::Operator);
    }
}
