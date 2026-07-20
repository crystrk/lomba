<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OperatorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Operator\DashboardController as OperatorDashboardController;
use App\Http\Middleware\CheckUserIsActive;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified', CheckUserIsActive::class])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('admin')->name('admin.')->middleware('can:admin-access')->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('operators', [OperatorController::class, 'index'])->name('operators.index');
        Route::get('operators/create', [OperatorController::class, 'create'])->name('operators.create');
        Route::post('operators', [OperatorController::class, 'store'])->name('operators.store');
        Route::get('operators/{user}/edit', [OperatorController::class, 'edit'])->name('operators.edit');
        Route::put('operators/{user}', [OperatorController::class, 'update'])->name('operators.update');
        Route::patch('operators/{user}/toggle-active', [OperatorController::class, 'toggleActive'])->name('operators.toggle-active');
    });

    Route::prefix('operator')->name('operator.')->middleware('can:operator-access')->group(function () {
        Route::get('dashboard', [OperatorDashboardController::class, 'index'])->name('dashboard');
    });
});

require __DIR__.'/settings.php';
