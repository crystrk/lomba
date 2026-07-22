<?php

namespace App\Http\Controllers\Admin;

use App\Calculators\StandingsCalculator;
use App\Enums\CompetitionMatchStatus;
use App\Http\Controllers\Controller;
use App\Models\Competition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ScoreController extends Controller
{
    public function __construct(
        private readonly StandingsCalculator $standingsCalculator,
    ) {}

    public function index(Request $request, Competition $competition): Response
    {
        Gate::authorize('updateScore', $competition);

        $competition->load(['participants', 'matches.homeParticipant', 'matches.awayParticipant', 'matches.winner']);

        $standings = [];
        if ($competition->usesPoints()) {
            $completedMatches = $competition->matches
                ->where('status', CompetitionMatchStatus::Completed->value)
                ->whereNotNull('participant_id_home')
                ->whereNotNull('participant_id_away');

            $standings = $this->standingsCalculator->calculate(
                $competition,
                $competition->participants,
                $completedMatches,
            );
        }

        $matches = $competition->matches
            ->groupBy(fn ($m) => $m->round)
            ->sortKeys();

        return Inertia::render('Admin/Competitions/Scores', [
            'competition' => $competition,
            'matchesByRound' => $matches,
            'standings' => $standings,
        ]);
    }

    public function toggleLockResults(Request $request, Competition $competition): RedirectResponse
    {
        Gate::authorize('lockResults', $competition);

        $isLocked = ! $competition->is_results_locked;

        if ($isLocked && ! $competition->isCompleted()) {
            return redirect()->back()->withErrors([
                'competition' => 'Hasil pertandingan hanya dapat dikunci setelah seluruh pertandingan selesai dimainkan.',
            ]);
        }

        $competition->update([
            'is_results_locked' => $isLocked,
            'results_locked_by' => $isLocked ? $request->user()->id : null,
            'results_locked_at' => $isLocked ? now() : null,
        ]);

        $message = $isLocked
            ? 'Hasil pertandingan berhasil dikunci final.'
            : 'Kunci hasil pertandingan telah dibuka.';

        return redirect()->back()->with('success', $message);
    }
}
