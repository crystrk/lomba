<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompetitionMatchStatus;
use App\Enums\CompetitionStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\CompetitionMatch;
use App\Models\Participant;
use App\Models\User;
use Inertia\Response;
use Inertia\ResponseFactory;

class DashboardController extends Controller
{
    public function index(): Response|ResponseFactory
    {
        $countByStatus = Competition::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $recentCompetitions = Competition::query()
            ->withCount(['participants', 'matches'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (Competition $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'format' => $c->format->value,
                'status' => $c->status->value,
                'participants_count' => $c->participants_count,
                'matches_count' => $c->matches_count,
                'created_at' => $c->created_at?->format('d M Y, H:i'),
                'starts_at' => $c->starts_at?->format('d M Y, H:i'),
            ]);

        $activeCompetitions = Competition::query()
            ->whereIn('status', [CompetitionStatus::InProgress->value, CompetitionStatus::Locked->value])
            ->withCount([
                'participants',
                'matches',
                'matches as completed_matches_count' => function ($query) {
                    $query->where('status', CompetitionMatchStatus::Completed->value);
                },
            ])
            ->latest('updated_at')
            ->take(5)
            ->get()
            ->map(fn (Competition $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'format' => $c->format->value,
                'status' => $c->status->value,
                'participants_count' => $c->participants_count,
                'matches_count' => $c->matches_count,
                'completed_matches_count' => $c->completed_matches_count,
            ]);

        return Inertia('Admin/Dashboard', [
            'stats' => [
                'total' => Competition::count(),
                'draft' => (int) $countByStatus->get(CompetitionStatus::Draft->value, 0),
                'drawn' => (int) $countByStatus->get(CompetitionStatus::Drawn->value, 0),
                'locked' => (int) $countByStatus->get(CompetitionStatus::Locked->value, 0),
                'in_progress' => (int) $countByStatus->get(CompetitionStatus::InProgress->value, 0),
                'completed' => (int) $countByStatus->get(CompetitionStatus::Completed->value, 0),
                'total_participants' => Participant::count(),
                'total_operators' => User::where('role', UserRole::Operator->value)->count(),
                'total_matches' => CompetitionMatch::count(),
            ],
            'recent_competitions' => $recentCompetitions,
            'active_competitions' => $activeCompetitions,
        ]);
    }
}
