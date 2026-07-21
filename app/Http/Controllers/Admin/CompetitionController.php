<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompetitionFormat;
use App\Enums\CompetitionStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCompetitionRequest;
use App\Http\Requests\Admin\SyncOperatorsRequest;
use App\Http\Requests\Admin\UpdateCompetitionRequest;
use App\Models\Competition;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Response;
use Inertia\ResponseFactory;

class CompetitionController extends Controller
{
    public function index(): Response|ResponseFactory
    {
        $competitions = Competition::query()
            ->withCount('participants')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (Competition $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'format' => $c->format->value,
                'status' => $c->status->value,
                'participants_count' => $c->participants_count,
                'starts_at' => $c->starts_at?->format('Y-m-d'),
                'ends_at' => $c->ends_at?->format('Y-m-d'),
                'created_at' => $c->created_at->format('Y-m-d H:i'),
            ]);

        return Inertia('Admin/Competitions/Index', [
            'competitions' => $competitions,
        ]);
    }

    public function create(): Response|ResponseFactory
    {
        return Inertia('Admin/Competitions/Create', [
            'formats' => collect(CompetitionFormat::cases())->map->value,
        ]);
    }

    public function store(StoreCompetitionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->generateUniqueSlug($data['name']);
        $data['status'] = CompetitionStatus::Draft;
        $data['draw_version'] = 0;

        if ($data['format'] === 'knockout') {
            $data['win_points'] = null;
            $data['draw_points'] = null;
            $data['loss_points'] = null;
        }

        Competition::create($data);

        return redirect()->route('admin.competitions.index')
            ->with('success', 'Lomba berhasil dibuat.');
    }

    public function show(Competition $competition): Response|ResponseFactory
    {
        $competition->loadCount('participants');

        return Inertia('Admin/Competitions/Show', [
            'competition' => [
                'id' => $competition->id,
                'name' => $competition->name,
                'slug' => $competition->slug,
                'description' => $competition->description,
                'format' => $competition->format->value,
                'status' => $competition->status->value,
                'win_points' => $competition->win_points,
                'draw_points' => $competition->draw_points,
                'loss_points' => $competition->loss_points,
                'participants_count' => $competition->participants_count,
                'starts_at' => $competition->starts_at?->format('Y-m-d'),
                'ends_at' => $competition->ends_at?->format('Y-m-d'),
            ],
        ]);
    }

    public function edit(Competition $competition): Response|ResponseFactory
    {
        return Inertia('Admin/Competitions/Edit', [
            'competition' => [
                'id' => $competition->id,
                'name' => $competition->name,
                'slug' => $competition->slug,
                'description' => $competition->description,
                'format' => $competition->format->value,
                'status' => $competition->status->value,
                'win_points' => $competition->win_points,
                'draw_points' => $competition->draw_points,
                'loss_points' => $competition->loss_points,
                'starts_at' => $competition->starts_at?->format('Y-m-d'),
                'ends_at' => $competition->ends_at?->format('Y-m-d'),
            ],
            'formats' => collect(CompetitionFormat::cases())->map->value,
        ]);
    }

    public function update(UpdateCompetitionRequest $request, Competition $competition): RedirectResponse
    {
        $data = $request->validated();

        if (isset($data['name']) && $data['name'] !== $competition->name) {
            $data['slug'] = $this->generateUniqueSlug($data['name']);
        }

        if (isset($data['format']) && $data['format'] === 'knockout') {
            $data['win_points'] = null;
            $data['draw_points'] = null;
            $data['loss_points'] = null;
        }

        $competition->update($data);

        return redirect()->route('admin.competitions.index')
            ->with('success', 'Lomba berhasil diperbarui.');
    }

    public function destroy(Competition $competition): RedirectResponse
    {
        Gate::authorize('delete', $competition);

        $competition->delete();

        return redirect()->route('admin.competitions.index')
            ->with('success', 'Lomba berhasil dihapus.');
    }

    public function operators(Competition $competition): Response|ResponseFactory
    {
        $assignedIds = $competition->operators()
            ->pluck('users.id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $allOperators = User::where('role', UserRole::Operator->value)
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id' => (int) $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'is_active' => (bool) $u->is_active,
            ]);

        return Inertia('Admin/Competitions/Operators', [
            'competition' => [
                'id' => $competition->id,
                'name' => $competition->name,
            ],
            'operators' => $allOperators,
            'assigned_ids' => $assignedIds,
        ]);
    }

    public function syncOperators(SyncOperatorsRequest $request, Competition $competition): RedirectResponse
    {
        $operatorIds = $request->validated()['operator_ids'];

        $validOperators = User::whereIn('id', $operatorIds)
            ->where('role', UserRole::Operator->value)
            ->where('is_active', true)
            ->pluck('id');

        $syncData = [];
        foreach ($validOperators as $id) {
            $syncData[$id] = [
                'assigned_by' => $request->user()->id,
                'assigned_at' => now(),
            ];
        }

        $competition->operators()->sync($syncData);

        return redirect()->route('admin.competitions.operators', $competition)
            ->with('success', 'Operator berhasil disinkronkan.');
    }

    private function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $original = $slug;

        $counter = 1;
        while (Competition::where('slug', $slug)->exists()) {
            $slug = $original.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
