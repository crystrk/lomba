<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->role === UserRole::Admin) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('operator.dashboard');
    }
}
