<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOperatorRequest;
use App\Http\Requests\Admin\UpdateOperatorRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class OperatorController extends Controller
{
    public function index(): Response
    {
        $operators = User::where('role', UserRole::Operator)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'created_at' => $user->created_at?->toDateTimeString(),
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

        return Inertia::render('Admin/Operators/Edit', [
            'operator' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
            ],
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
}
