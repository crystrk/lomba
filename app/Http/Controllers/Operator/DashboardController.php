<?php

namespace App\Http\Controllers\Operator;

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
            ->withCount('participants', 'matches')
            ->get()
            ->map(fn (Competition $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'format' => $c->format->value,
                'status' => $c->status->value,
                'participants_count' => $c->participants_count,
                'matches_count' => $c->matches_count,
            ]);

        return Inertia::render('Operator/Dashboard', [
            'competitions' => $competitions,
        ]);
    }
}
