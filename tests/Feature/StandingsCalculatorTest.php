<?php

use App\Calculators\StandingsCalculator;
use App\Enums\CompetitionFormat;
use App\Models\Competition;
use App\Models\CompetitionMatch;
use App\Models\Participant;

beforeEach(function () {
    $this->calculator = app(StandingsCalculator::class);
    $this->competition = Competition::factory()->create([
        'format' => CompetitionFormat::HalfCompetition,
        'win_points' => 3,
        'draw_points' => 1,
        'loss_points' => 0,
    ]);
});

it('returns empty array for no participants', function () {
    $result = $this->calculator->calculate(
        $this->competition,
        collect(),
        collect(),
    );

    expect($result)->toBe([]);
});

it('shows participants with zero stats when no matches played', function () {
    $participants = Participant::factory()
        ->count(2)
        ->for($this->competition)
        ->create();

    $result = $this->calculator->calculate(
        $this->competition,
        $participants,
        collect(),
    );

    expect($result)->toHaveCount(2);
    expect($result[0]->played)->toBe(0);
    expect($result[0]->won)->toBe(0);
    expect($result[0]->points)->toBe(0);
});

it('calculates standings correctly for won match', function () {
    $participants = Participant::factory()
        ->count(2)
        ->for($this->competition)
        ->create();

    $match = CompetitionMatch::factory()->create([
        'competition_id' => $this->competition->id,
        'participant_id_home' => $participants[0]->id,
        'participant_id_away' => $participants[1]->id,
        'score_home' => 3,
        'score_away' => 1,
        'status' => 'completed',
    ]);

    $result = $this->calculator->calculate(
        $this->competition,
        $participants,
        collect([$match]),
    );

    expect($result)->toHaveCount(2);

    $winner = $result[0];
    expect($winner->participantId)->toBe($participants[0]->id);
    expect($winner->played)->toBe(1);
    expect($winner->won)->toBe(1);
    expect($winner->drawn)->toBe(0);
    expect($winner->lost)->toBe(0);
    expect($winner->scoreFor)->toBe(3);
    expect($winner->scoreAgainst)->toBe(1);
    expect($winner->difference)->toBe(2);
    expect($winner->points)->toBe(3);

    $loser = $result[1];
    expect($loser->participantId)->toBe($participants[1]->id);
    expect($loser->played)->toBe(1);
    expect($loser->won)->toBe(0);
    expect($loser->lost)->toBe(1);
    expect($loser->points)->toBe(0);
});

it('calculates draw correctly', function () {
    $participants = Participant::factory()
        ->count(2)
        ->for($this->competition)
        ->create();

    $match = CompetitionMatch::factory()->create([
        'competition_id' => $this->competition->id,
        'participant_id_home' => $participants[0]->id,
        'participant_id_away' => $participants[1]->id,
        'score_home' => 2,
        'score_away' => 2,
        'status' => 'completed',
    ]);

    $result = $this->calculator->calculate(
        $this->competition,
        $participants,
        collect([$match]),
    );

    expect($result[0]->drawn)->toBe(1);
    expect($result[0]->points)->toBe(1);
    expect($result[1]->drawn)->toBe(1);
    expect($result[1]->points)->toBe(1);
});

it('uses custom win points', function () {
    $this->competition->update(['win_points' => 5, 'draw_points' => 2, 'loss_points' => -1]);

    $participants = Participant::factory()
        ->count(2)
        ->for($this->competition)
        ->create();

    $match = CompetitionMatch::factory()->create([
        'competition_id' => $this->competition->id,
        'participant_id_home' => $participants[0]->id,
        'participant_id_away' => $participants[1]->id,
        'score_home' => 1,
        'score_away' => 0,
        'status' => 'completed',
    ]);

    $result = $this->calculator->calculate(
        $this->competition->fresh(),
        $participants,
        collect([$match]),
    );

    expect($result[0]->points)->toBe(5);
    expect($result[1]->points)->toBe(-1);
});

it('sorts by points descending', function () {
    $participants = Participant::factory()
        ->count(3)
        ->for($this->competition)
        ->create();

    $m1 = CompetitionMatch::factory()->create([
        'competition_id' => $this->competition->id,
        'round' => 1,
        'sequence' => 1,
        'participant_id_home' => $participants[0]->id,
        'participant_id_away' => $participants[1]->id,
        'score_home' => 1,
        'score_away' => 0,
        'status' => 'completed',
    ]);

    $m2 = CompetitionMatch::factory()->create([
        'competition_id' => $this->competition->id,
        'round' => 1,
        'sequence' => 2,
        'participant_id_home' => $participants[2]->id,
        'participant_id_away' => $participants[1]->id,
        'score_home' => 2,
        'score_away' => 0,
        'status' => 'completed',
    ]);

    $result = $this->calculator->calculate(
        $this->competition,
        $participants,
        collect([$m1, $m2]),
    );

    expect($result[0]->points)->toBeGreaterThanOrEqual($result[1]->points);
    expect($result[1]->points)->toBeGreaterThanOrEqual($result[2]->points);
});

it('applies shared rank for tied participants', function () {
    $participants = Participant::factory()
        ->count(3)
        ->for($this->competition)
        ->create();

    $match = CompetitionMatch::factory()->create([
        'competition_id' => $this->competition->id,
        'participant_id_home' => $participants[0]->id,
        'participant_id_away' => $participants[1]->id,
        'score_home' => 1,
        'score_away' => 1,
        'status' => 'completed',
    ]);

    $result = $this->calculator->calculate(
        $this->competition,
        $participants,
        collect([$match]),
    );

    expect($result[0]->rank)->toBe(1);
    expect($result[1]->rank)->toBe(1);
    expect($result[2]->rank)->toBe(3);
});

it('tie-breaks by goal difference then score for then wins', function () {
    $participants = Participant::factory()
        ->count(2)
        ->for($this->competition)
        ->create();

    CompetitionMatch::factory()->create([
        'competition_id' => $this->competition->id,
        'participant_id_home' => $participants[0]->id,
        'participant_id_away' => $participants[1]->id,
        'score_home' => 3,
        'score_away' => 1,
        'status' => 'completed',
    ]);

    $result = $this->calculator->calculate(
        $this->competition,
        $participants,
        $this->competition->matches()->where('status', 'completed')->get(),
    );

    expect($result[0]->difference)->toBe(2);
    expect($result[1]->difference)->toBe(-2);
});

it('excludes bye matches from calculation', function () {
    $participants = Participant::factory()
        ->count(2)
        ->for($this->competition)
        ->create();

    $byeMatch = CompetitionMatch::factory()->create([
        'competition_id' => $this->competition->id,
        'participant_id_home' => $participants[0]->id,
        'participant_id_away' => null,
        'score_home' => null,
        'score_away' => null,
        'status' => 'bye',
    ]);

    $result = $this->calculator->calculate(
        $this->competition,
        $participants,
        collect([$byeMatch]),
    );

    expect($result[0]->played)->toBe(0);
    expect($result[1]->played)->toBe(0);
});

it('handles correction by recalculating from source matches', function () {
    $participants = Participant::factory()
        ->count(2)
        ->for($this->competition)
        ->create();

    $match = CompetitionMatch::factory()->create([
        'competition_id' => $this->competition->id,
        'participant_id_home' => $participants[0]->id,
        'participant_id_away' => $participants[1]->id,
        'score_home' => 2,
        'score_away' => 2,
        'status' => 'completed',
    ]);

    $result1 = $this->calculator->calculate(
        $this->competition,
        $participants,
        collect([$match]),
    );

    expect($result1[0]->points)->toBe(1);

    $match->update(['score_home' => 3, 'score_away' => 1, 'winner_id' => $participants[0]->id]);

    $result2 = $this->calculator->calculate(
        $this->competition->fresh(),
        $participants,
        collect([$match->fresh()]),
    );

    expect($result2[0]->points)->toBe(3);
    expect($result2[1]->points)->toBe(0);
});
