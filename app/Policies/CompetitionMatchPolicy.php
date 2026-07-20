<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\CompetitionMatch;
use App\Models\User;

class CompetitionMatchPolicy
{
    public function before(User $user): ?bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        return null;
    }

    public function updateScore(User $user, CompetitionMatch $match): bool
    {
        if ($user->role !== UserRole::Operator) {
            return false;
        }

        return $user->assignedCompetitions()
            ->where('competition_id', $match->competition_id)
            ->exists();
    }
}
