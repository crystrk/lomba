<?php

namespace Database\Factories;

use App\Enums\CompetitionMatchStatus;
use App\Models\Competition;
use App\Models\CompetitionMatch;
use App\Models\Participant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompetitionMatch>
 */
class CompetitionMatchFactory extends Factory
{
    protected $model = CompetitionMatch::class;

    public function definition(): array
    {
        return [
            'competition_id' => Competition::factory(),
            'round' => 1,
            'leg' => 1,
            'sequence' => 1,
            'participant_id_home' => Participant::factory(),
            'participant_id_away' => Participant::factory(),
            'score_home' => null,
            'score_away' => null,
            'winner_id' => null,
            'win_method' => null,
            'status' => CompetitionMatchStatus::Ready,
            'next_match_id' => null,
            'next_slot' => null,
            'result_version' => 0,
            'result_updated_by' => null,
            'result_updated_at' => null,
        ];
    }
}
