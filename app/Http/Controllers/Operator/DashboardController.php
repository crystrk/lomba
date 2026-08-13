<?php

namespace App\Http\Controllers\Operator;

use App\Enums\CompetitionMatchStatus;
use App\Http\Controllers\Controller;
use App\Models\Competition;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $competitions = $request->user()->assignedCompetitions()
            ->withCount([
                'participants',
                'matches',
                'matches as completed_matches_count' => function ($q) {
                    $q->where('status', CompetitionMatchStatus::Completed);
                },
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (Competition $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'sport' => $c->sport?->value,
                'format' => $c->format->value,
                'status' => $c->status->value,
                'participants_count' => $c->participants_count,
                'matches_count' => $c->matches_count,
                'completed_matches_count' => $c->completed_matches_count,
            ]);

        return Inertia::render('Operator/Dashboard', [
            'competitions' => $competitions,
        ]);
    }
}
