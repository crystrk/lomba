<?php

namespace App\Http\Controllers\Admin;

use App\Calculators\StandingsCalculator;
use App\Enums\CompetitionMatchStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\MatchScoreController;
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

        if ($competition->isGroupFinalFour()) {
            app(MatchScoreController::class)->checkAndPopulateGroupFinalFours($competition);
            $competition->refresh();
        }

        $competition->load(['participants', 'matches.homeParticipant', 'matches.awayParticipant', 'matches.winner']);

        $standings = [];
        $groupStandings = null;
        if ($competition->usesPoints()) {
            $completedMatches = $competition->matches
                ->where('status', CompetitionMatchStatus::Completed->value)
                ->whereNotNull('participant_id_home')
                ->whereNotNull('participant_id_away');

            if ($competition->isGroupFinalFour()) {
                $orderedParticipants = $competition->participants()->orderBy('draw_position')->orderBy('id')->get();
                $halfCount = intdiv($orderedParticipants->count(), 2);
                $groupAParticipants = $orderedParticipants->take($halfCount);
                $groupBParticipants = $orderedParticipants->skip($halfCount)->take($halfCount);

                $groupAMatches = $completedMatches->where('match_type', 'group_a');
                $groupBMatches = $completedMatches->where('match_type', 'group_b');

                $standingsA = array_map(fn ($e) => [
                    'rank' => $e->rank,
                    'participant_id' => $e->participantId,
                    'participant_name' => $e->participantName,
                    'played' => $e->played,
                    'won' => $e->won,
                    'drawn' => $e->drawn,
                    'lost' => $e->lost,
                    'score_for' => $e->scoreFor,
                    'score_against' => $e->scoreAgainst,
                    'difference' => $e->difference,
                    'points' => $e->points,
                ], $this->standingsCalculator->calculate($competition, $groupAParticipants, $groupAMatches));

                $standingsB = array_map(fn ($e) => [
                    'rank' => $e->rank,
                    'participant_id' => $e->participantId,
                    'participant_name' => $e->participantName,
                    'played' => $e->played,
                    'won' => $e->won,
                    'drawn' => $e->drawn,
                    'lost' => $e->lost,
                    'score_for' => $e->scoreFor,
                    'score_against' => $e->scoreAgainst,
                    'difference' => $e->difference,
                    'points' => $e->points,
                ], $this->standingsCalculator->calculate($competition, $groupBParticipants, $groupBMatches));

                $groupStandings = [
                    'group_a' => $standingsA,
                    'group_b' => $standingsB,
                ];
                $standings = array_merge($standingsA, $standingsB);
            } else {
                $standings = array_map(fn ($e) => [
                    'rank' => $e->rank,
                    'participant_id' => $e->participantId,
                    'participant_name' => $e->participantName,
                    'played' => $e->played,
                    'won' => $e->won,
                    'drawn' => $e->drawn,
                    'lost' => $e->lost,
                    'score_for' => $e->scoreFor,
                    'score_against' => $e->scoreAgainst,
                    'difference' => $e->difference,
                    'points' => $e->points,
                ], $this->standingsCalculator->calculate($competition, $competition->participants, $completedMatches));
            }
        }

        $matches = $competition->matches
            ->groupBy(fn ($m) => $m->round)
            ->sortKeys();

        return Inertia::render('Admin/Competitions/Scores', [
            'competition' => $competition,
            'matchesByRound' => $matches,
            'standings' => $standings,
            'groupStandings' => $groupStandings,
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
