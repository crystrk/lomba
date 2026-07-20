<?php

namespace App\Policies;

use App\Enums\CompetitionStatus;
use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\User;

class CompetitionPolicy
{
    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function update(User $user, Competition $competition): bool
    {
        if ($user->role !== UserRole::Admin) {
            return false;
        }

        return in_array($competition->status, [CompetitionStatus::Draft, CompetitionStatus::Drawn]);
    }

    public function delete(User $user, Competition $competition): bool
    {
        if ($user->role !== UserRole::Admin) {
            return false;
        }

        return in_array($competition->status, [CompetitionStatus::Draft, CompetitionStatus::Drawn]);
    }

    public function draw(User $user, Competition $competition): bool
    {
        if ($user->role !== UserRole::Admin) {
            return false;
        }

        return in_array($competition->status, [CompetitionStatus::Draft, CompetitionStatus::Drawn]);
    }

    public function lock(User $user, Competition $competition): bool
    {
        if ($user->role !== UserRole::Admin) {
            return false;
        }

        return $competition->status === CompetitionStatus::Drawn;
    }

    public function updateScore(User $user, Competition $competition): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        if ($user->role === UserRole::Operator) {
            return $competition->status !== CompetitionStatus::Draft
                && $user->assignedCompetitions()->where('competition_id', $competition->id)->exists();
        }

        return false;
    }

    public function view(User $user, Competition $competition): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        return $user->role === UserRole::Operator
            && $user->assignedCompetitions()->where('competition_id', $competition->id)->exists();
    }

    public function viewInternal(User $user, Competition $competition): bool
    {
        return $user->role === UserRole::Operator
            && $user->assignedCompetitions()->where('competition_id', $competition->id)->exists();
    }
}
