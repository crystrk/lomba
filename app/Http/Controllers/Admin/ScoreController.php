<?php

namespace App\Http\Controllers\Admin;

use App\Calculators\StandingsCalculator;
use App\Enums\CompetitionMatchStatus;
use App\Http\Controllers\Controller;
use App\Models\Competition;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScoreController extends Controller
{
    public function __construct(
        private readonly StandingsCalculator $standingsCalculator,
    ) {}

    public function index(Request $request, Competition $competition): Response
    {
        $this->authorize('updateScore', $competition);

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
}
