<?php

namespace App\Http\Controllers;

use App\Calculators\StandingsCalculator;
use App\Enums\CompetitionMatchStatus;
use App\Enums\CompetitionStatus;
use App\Models\Competition;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicCompetitionController extends Controller
{
    public function __construct(
        private readonly StandingsCalculator $standingsCalculator,
    ) {}

    public function index(): Response
    {
        $competitions = Competition::query()
            ->whereIn('status', [CompetitionStatus::Locked, CompetitionStatus::InProgress, CompetitionStatus::Completed])
            ->withCount('participants')
            ->withCount([
                'matches as total_scorable_matches' => function ($q) {
                    $q->whereNotNull('participant_id_home')
                        ->whereNotNull('participant_id_away');
                },
                'matches as completed_scorable_matches' => function ($q) {
                    $q->where('status', CompetitionMatchStatus::Completed)
                        ->whereNotNull('participant_id_home')
                        ->whereNotNull('participant_id_away');
                },
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (Competition $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'format' => $c->format->value,
                'status' => $c->status->value,
                'participants_count' => $c->participants_count,
                'completed_matches' => $c->completed_scorable_matches,
                'total_matches' => $c->total_scorable_matches,
            ]);

        return Inertia::render('Welcome', [
            'competitions' => $competitions,
        ]);
    }

    public function show(Request $request, Competition $competition): Response
    {
        if (in_array($competition->status->value, [CompetitionStatus::Draft->value, CompetitionStatus::Drawn->value])) {
            abort(404);
        }

        $competition->load(['participants', 'matches.homeParticipant', 'matches.awayParticipant', 'matches.winner']);

        $matches = $competition->matches
            ->groupBy(fn ($m) => $m->round)
            ->sortKeys()
            ->map(fn ($roundMatches) => $roundMatches->map(fn ($m) => [
                'id' => $m->id,
                'round' => $m->round,
                'leg' => $m->leg,
                'sequence' => $m->sequence,
                'home' => $m->homeParticipant ? ['id' => $m->homeParticipant->id, 'name' => $m->homeParticipant->name] : null,
                'away' => $m->awayParticipant ? ['id' => $m->awayParticipant->id, 'name' => $m->awayParticipant->name] : null,
                'score_home' => $m->score_home,
                'score_away' => $m->score_away,
                'winner_id' => $m->winner_id,
                'status' => $m->status->value,
                'win_method' => $m->win_method,
                'next_match_id' => $m->next_match_id,
                'next_slot' => $m->next_slot,
            ])->values())
            ->toArray();

        $participants = $competition->participants->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'short_name' => $p->short_name,
        ]);

        $standings = [];
        if ($competition->usesPoints()) {
            $completedMatches = $competition->matches
                ->where('status', CompetitionMatchStatus::Completed->value)
                ->whereNotNull('participant_id_home')
                ->whereNotNull('participant_id_away');

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

        return Inertia::render('Public/Competition/Show', [
            'competition' => [
                'id' => $competition->id,
                'name' => $competition->name,
                'slug' => $competition->slug,
                'format' => $competition->format->value,
                'status' => $competition->status->value,
                'win_points' => $competition->win_points,
                'draw_points' => $competition->draw_points,
                'loss_points' => $competition->loss_points,
            ],
            'participants' => $participants,
            'matchesByRound' => $matches,
            'standings' => $standings,
        ]);
    }
}
