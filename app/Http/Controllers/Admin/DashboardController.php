<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompetitionStatus;
use App\Http\Controllers\Controller;
use App\Models\Competition;
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

        return Inertia('Admin/Dashboard', [
            'stats' => [
                'total' => Competition::count(),
                'draft' => $countByStatus->get(CompetitionStatus::Draft->value, 0),
                'drawn' => $countByStatus->get(CompetitionStatus::Drawn->value, 0),
                'locked' => $countByStatus->get(CompetitionStatus::Locked->value, 0),
                'in_progress' => $countByStatus->get(CompetitionStatus::InProgress->value, 0),
                'completed' => $countByStatus->get(CompetitionStatus::Completed->value, 0),
            ],
        ]);
    }
}
