<?php

use App\Http\Controllers\Admin\CompetitionController;
use App\Http\Controllers\Admin\CompetitionDrawController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OperatorController;
use App\Http\Controllers\Admin\ParticipantController;
use App\Http\Controllers\Admin\ScoreController as AdminScoreController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MatchScoreController;
use App\Http\Controllers\Operator\DashboardController as OperatorDashboardController;
use App\Http\Controllers\Operator\ScoreController as OperatorScoreController;
use App\Http\Controllers\PublicCompetitionController;
use App\Http\Middleware\CheckUserIsActive;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicCompetitionController::class, 'index'])->name('home');
Route::get('lomba/{competition:slug}', [PublicCompetitionController::class, 'show'])->name('public.competitions.show');

Route::middleware(['auth', 'verified', CheckUserIsActive::class])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('admin')->name('admin.')->middleware('can:admin-access')->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('competitions', [CompetitionController::class, 'index'])->name('competitions.index');
        Route::get('competitions/create', [CompetitionController::class, 'create'])->name('competitions.create');
        Route::post('competitions', [CompetitionController::class, 'store'])->name('competitions.store');
        Route::get('competitions/{competition}', [CompetitionController::class, 'show'])->name('competitions.show');
        Route::get('competitions/{competition}/edit', [CompetitionController::class, 'edit'])->name('competitions.edit');
        Route::put('competitions/{competition}', [CompetitionController::class, 'update'])->name('competitions.update');
        Route::delete('competitions/{competition}', [CompetitionController::class, 'destroy'])->name('competitions.destroy');

        Route::get('competitions/{competition}/participants', [ParticipantController::class, 'index'])->name('competitions.participants.index');
        Route::get('competitions/{competition}/participants/create', [ParticipantController::class, 'create'])->name('competitions.participants.create');
        Route::post('competitions/{competition}/participants', [ParticipantController::class, 'store'])->name('competitions.participants.store');
        Route::get('competitions/{competition}/participants/{participant}/edit', [ParticipantController::class, 'edit'])->name('competitions.participants.edit');
        Route::put('competitions/{competition}/participants/{participant}', [ParticipantController::class, 'update'])->name('competitions.participants.update');
        Route::delete('competitions/{competition}/participants/{participant}', [ParticipantController::class, 'destroy'])->name('competitions.participants.destroy');

        Route::get('competitions/{competition}/operators', [CompetitionController::class, 'operators'])->name('competitions.operators');
        Route::put('competitions/{competition}/operators', [CompetitionController::class, 'syncOperators'])->name('competitions.operators.sync');

        Route::get('competitions/{competition}/draw', [CompetitionDrawController::class, 'show'])->name('competitions.draw.show');
        Route::post('competitions/{competition}/shuffle', [CompetitionDrawController::class, 'shuffle'])->name('competitions.shuffle');
        Route::post('competitions/{competition}/lock', [CompetitionDrawController::class, 'lock'])->name('competitions.lock');

        Route::get('competitions/{competition}/scores', [AdminScoreController::class, 'index'])->name('competitions.scores');
        Route::post('competitions/{competition}/matches/{match}/score', [MatchScoreController::class, 'update'])->name('matches.score.update');

        Route::get('operators', [OperatorController::class, 'index'])->name('operators.index');
        Route::get('operators/create', [OperatorController::class, 'create'])->name('operators.create');
        Route::post('operators', [OperatorController::class, 'store'])->name('operators.store');
        Route::get('operators/{user}/edit', [OperatorController::class, 'edit'])->name('operators.edit');
        Route::put('operators/{user}', [OperatorController::class, 'update'])->name('operators.update');
        Route::patch('operators/{user}/toggle-active', [OperatorController::class, 'toggleActive'])->name('operators.toggle-active');
    });

    Route::prefix('operator')->name('operator.')->middleware('can:operator-access')->group(function () {
        Route::get('dashboard', [OperatorDashboardController::class, 'index'])->name('dashboard');
        Route::get('competitions/{competition}/scores', [OperatorScoreController::class, 'index'])->name('competitions.scores');
        Route::post('competitions/{competition}/matches/{match}/score', [MatchScoreController::class, 'update'])->name('matches.score.update');
    });
});

require __DIR__.'/settings.php';
