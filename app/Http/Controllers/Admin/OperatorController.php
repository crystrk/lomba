<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompetitionStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOperatorRequest;
use App\Http\Requests\Admin\UpdateOperatorRequest;
use App\Models\Competition;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class OperatorController extends Controller
{
    public function index(): Response
    {
        $operators = User::where('role', UserRole::Operator)
            ->withCount('assignedCompetitions')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'created_at' => $user->created_at?->toDateTimeString(),
                'assigned_competitions_count' => $user->assigned_competitions_count,
            ]);

        return Inertia::render('Admin/Operators/Index', [
            'operators' => $operators,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Operators/Create');
    }

    public function store(StoreOperatorRequest $request): RedirectResponse
    {
        $operator = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => UserRole::Operator,
            'is_active' => true,
        ]);

        $operator->markEmailAsVerified();

        return redirect()->route('admin.operators.index')
            ->with('success', 'Akun operator berhasil dibuat.');
    }

    public function edit(User $user): Response
    {
        abort_if($user->role !== UserRole::Operator, 404);

        $competitions = Competition::whereNot('status', CompetitionStatus::Draft)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (Competition $competition) => [
                'id' => $competition->id,
                'name' => $competition->name,
                'format' => $competition->format,
                'status' => $competition->status,
            ]);

        $assigned_competition_ids = $user->assignedCompetitions()
            ->pluck('competitions.id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return Inertia::render('Admin/Operators/Edit', [
            'operator' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
            ],
            'competitions' => $competitions,
            'assigned_competition_ids' => $assigned_competition_ids,
        ]);
    }

    public function update(UpdateOperatorRequest $request, User $user): RedirectResponse
    {
        abort_if($user->role !== UserRole::Operator, 404);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('admin.operators.index')
            ->with('success', 'Akun operator berhasil diperbarui.');
    }

    public function toggleActive(User $user): RedirectResponse
    {
        abort_if($user->role !== UserRole::Operator, 404);

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('admin.operators.index')
            ->with('success', "Akun operator berhasil {$status}.");
    }

    public function syncCompetitions(Request $request, User $user): RedirectResponse
    {
        abort_if($user->role !== UserRole::Operator, 404);

        $validated = $request->validate([
            'competition_ids' => ['present', 'array'],
            'competition_ids.*' => ['integer', 'exists:competitions,id'],
        ]);

        $syncData = [];
        foreach ($validated['competition_ids'] as $id) {
            $syncData[$id] = [
                'assigned_by' => $request->user()->id,
                'assigned_at' => now(),
            ];
        }

        $user->assignedCompetitions()->sync($syncData);

        return redirect()->route('admin.operators.edit', $user)
            ->with('success', 'Penugasan lomba berhasil diperbarui.');
    }
}
