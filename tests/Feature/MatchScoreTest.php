<?php

use App\Enums\CompetitionFormat;
use App\Enums\CompetitionMatchStatus;
use App\Enums\CompetitionStatus;
use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\CompetitionMatch;
use App\Models\Participant;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->operator = User::factory()->create(['role' => UserRole::Operator, 'is_active' => true]);
    $this->inactiveOperator = User::factory()->create(['role' => UserRole::Operator, 'is_active' => false]);
    $this->unassignedOperator = User::factory()->create(['role' => UserRole::Operator, 'is_active' => true]);
});

function createLockedCompetition(string $format = 'half_competition'): Competition
{
    $competition = Competition::factory()->create([
        'format' => $format,
        'status' => CompetitionStatus::Locked,
        'win_points' => 3,
        'draw_points' => 1,
        'loss_points' => 0,
    ]);

    return $competition;
}

function attachOperator(Competition $competition, User $operator): void
{
    $competition->operators()->attach($operator->id, [
        'assigned_by' => User::factory()->create(['role' => UserRole::Admin])->id,
        'assigned_at' => now(),
    ]);
}

function createMatchWithParticipants(Competition $competition, ?Participant $home = null, ?Participant $away = null): CompetitionMatch
{
    if ($home === null) {
        $home = Participant::factory()->for($competition)->create();
    }
    if ($away === null) {
        $away = Participant::factory()->for($competition)->create();
    }

    return CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'participant_id_home' => $home->id,
        'participant_id_away' => $away->id,
        'status' => CompetitionMatchStatus::Ready,
        'result_version' => 0,
    ]);
}

// C2 — Score submission validation

it('admin can update match score', function () {
    $competition = createLockedCompetition();
    $match = createMatchWithParticipants($competition);
    attachOperator($competition, $this->operator);

    $this->actingAs($this->admin)
        ->post(route('admin.matches.score.update', [$competition, $match]), [
            'score_home' => 3,
            'score_away' => 1,
            'result_version' => 0,
        ])
        ->assertRedirect();

    $match->refresh();
    expect($match->score_home)->toBe(3);
    expect($match->score_away)->toBe(1);
    expect($match->status)->toBe(CompetitionMatchStatus::Completed);
    expect($match->result_version)->toBe(1);
    expect($match->result_updated_by)->toBe($this->admin->id);
});

it('assigned active operator can update match score', function () {
    $competition = createLockedCompetition();
    $match = createMatchWithParticipants($competition);
    attachOperator($competition, $this->operator);

    $this->actingAs($this->operator)
        ->post(route('operator.matches.score.update', [$competition, $match]), [
            'score_home' => 2,
            'score_away' => 0,
            'result_version' => 0,
        ])
        ->assertRedirect();

    $match->refresh();
    expect($match->score_home)->toBe(2);
});

it('guest cannot update match score', function () {
    $competition = createLockedCompetition();
    $match = createMatchWithParticipants($competition);

    $this->post(route('admin.matches.score.update', [$competition, $match]), [
        'score_home' => 1,
        'score_away' => 0,
        'result_version' => 0,
    ])->assertRedirect(route('login'));
});

it('inactive operator cannot update match score', function () {
    $competition = createLockedCompetition();
    $match = createMatchWithParticipants($competition);
    attachOperator($competition, $this->inactiveOperator);

    $this->actingAs($this->inactiveOperator)
        ->post(route('operator.matches.score.update', [$competition, $match]), [
            'score_home' => 1,
            'score_away' => 0,
            'result_version' => 0,
        ])
        ->assertRedirect();
});

it('unassigned operator cannot update match score', function () {
    $competition = createLockedCompetition();
    $match = createMatchWithParticipants($competition);

    $this->actingAs($this->unassignedOperator)
        ->post(route('operator.matches.score.update', [$competition, $match]), [
            'score_home' => 1,
            'score_away' => 0,
            'result_version' => 0,
        ])
        ->assertForbidden();
});

it('invalid negative score is rejected', function () {
    $competition = createLockedCompetition();
    $match = createMatchWithParticipants($competition);
    attachOperator($competition, $this->operator);

    $this->actingAs($this->admin)
        ->post(route('admin.matches.score.update', [$competition, $match]), [
            'score_home' => -1,
            'score_away' => 0,
            'result_version' => 0,
        ])
        ->assertSessionHasErrors('score_home');
});

it('bye match cannot have scores', function () {
    $competition = createLockedCompetition();
    $participant = Participant::factory()->for($competition)->create();
    $match = CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'participant_id_home' => $participant->id,
        'participant_id_away' => null,
        'status' => CompetitionMatchStatus::Bye,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.matches.score.update', [$competition, $match]), [
            'score_home' => 0,
            'score_away' => 0,
            'result_version' => 0,
        ])
        ->assertSessionHasErrors('match');
});

it('pending match cannot have scores', function () {
    $competition = createLockedCompetition();
    $match = CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'participant_id_home' => Participant::factory()->for($competition),
        'participant_id_away' => Participant::factory()->for($competition),
        'status' => CompetitionMatchStatus::Pending,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.matches.score.update', [$competition, $match]), [
            'score_home' => 1,
            'score_away' => 0,
            'result_version' => 0,
        ])
        ->assertSessionHasErrors('match');
});

it('draft competition cannot have scores', function () {
    $competition = Competition::factory()->create([
        'format' => 'half_competition',
        'status' => CompetitionStatus::Draft,
    ]);
    $match = createMatchWithParticipants($competition);

    $this->actingAs($this->admin)
        ->post(route('admin.matches.score.update', [$competition, $match]), [
            'score_home' => 1,
            'score_away' => 0,
            'result_version' => 0,
        ])
        ->assertSessionHasErrors('competition');
});

it('stale result version is rejected', function () {
    $competition = createLockedCompetition();
    $match = createMatchWithParticipants($competition);
    attachOperator($competition, $this->operator);

    $match->update(['result_version' => 1]);

    $this->actingAs($this->admin)
        ->post(route('admin.matches.score.update', [$competition, $match]), [
            'score_home' => 2,
            'score_away' => 1,
            'result_version' => 0,
        ])
        ->assertSessionHasErrors('result_version');

    $match->refresh();
    expect($match->result_version)->toBe(1);
    expect($match->score_home)->toBeNull();
});

// C3 — Competition format lifecycle

it('first score transitions competition to in_progress when more matches remain', function () {
    $competition = createLockedCompetition();
    $p1 = Participant::factory()->for($competition)->create();
    $p2 = Participant::factory()->for($competition)->create();
    $p3 = Participant::factory()->for($competition)->create();

    CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'round' => 1, 'sequence' => 1,
        'participant_id_home' => $p1->id, 'participant_id_away' => $p2->id,
        'status' => CompetitionMatchStatus::Ready,
    ]);
    $m2 = CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'round' => 1, 'sequence' => 2,
        'participant_id_home' => $p1->id, 'participant_id_away' => $p3->id,
        'status' => CompetitionMatchStatus::Ready,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.matches.score.update', [$competition, $m2]), [
            'score_home' => 1,
            'score_away' => 0,
            'result_version' => 0,
        ]);

    $competition->refresh();
    expect($competition->status)->toBe(CompetitionStatus::InProgress);
});

it('competition completes when all scorable matches are done', function () {
    $competition = createLockedCompetition();
    $p1 = Participant::factory()->for($competition)->create();
    $p2 = Participant::factory()->for($competition)->create();
    $p3 = Participant::factory()->for($competition)->create();

    $m1 = CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'round' => 1, 'sequence' => 1,
        'participant_id_home' => $p1->id, 'participant_id_away' => $p2->id,
        'status' => CompetitionMatchStatus::Ready,
    ]);
    $m2 = CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'round' => 1, 'sequence' => 2,
        'participant_id_home' => $p1->id, 'participant_id_away' => $p3->id,
        'status' => CompetitionMatchStatus::Ready,
    ]);
    $m3 = CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'round' => 1, 'sequence' => 3,
        'participant_id_home' => $p2->id, 'participant_id_away' => $p3->id,
        'status' => CompetitionMatchStatus::Ready,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.matches.score.update', [$competition, $m1]), ['score_home' => 1, 'score_away' => 0, 'result_version' => 0]);

    $this->actingAs($this->admin)
        ->post(route('admin.matches.score.update', [$competition, $m2]), ['score_home' => 2, 'score_away' => 0, 'result_version' => 0]);

    $this->actingAs($this->admin)
        ->post(route('admin.matches.score.update', [$competition, $m3]), ['score_home' => 1, 'score_away' => 1, 'result_version' => 0]);

    $competition->refresh();
    expect($competition->status)->toBe(CompetitionStatus::Completed);
});

it('can update score on completed competition', function () {
    $competition = createLockedCompetition();
    $p1 = Participant::factory()->for($competition)->create();
    $p2 = Participant::factory()->for($competition)->create();

    $m1 = CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'round' => 1, 'sequence' => 1,
        'participant_id_home' => $p1->id, 'participant_id_away' => $p2->id,
        'status' => CompetitionMatchStatus::Ready,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.matches.score.update', [$competition, $m1]), ['score_home' => 1, 'score_away' => 0, 'result_version' => 0]);

    $competition->refresh();
    expect($competition->status)->toBe(CompetitionStatus::Completed);

    $m1->refresh();
    $this->actingAs($this->admin)
        ->post(route('admin.matches.score.update', [$competition, $m1]), ['score_home' => 2, 'score_away' => 1, 'result_version' => 1])
        ->assertRedirect();

    $m1->refresh();
    expect($m1->score_home)->toBe(2);
    expect($m1->score_away)->toBe(1);
    expect($m1->result_version)->toBe(2);

    $competition->refresh();
    expect($competition->status)->toBe(CompetitionStatus::Completed);
});

it('does not complete if one scorable match remains pending', function () {
    $competition = createLockedCompetition();
    $p1 = Participant::factory()->for($competition)->create();
    $p2 = Participant::factory()->for($competition)->create();
    $p3 = Participant::factory()->for($competition)->create();

    $m1 = CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'round' => 1, 'sequence' => 1,
        'participant_id_home' => $p1->id, 'participant_id_away' => $p2->id,
        'status' => CompetitionMatchStatus::Ready,
    ]);
    CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'round' => 1, 'sequence' => 2,
        'participant_id_home' => $p1->id, 'participant_id_away' => $p3->id,
        'status' => CompetitionMatchStatus::Ready,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.matches.score.update', [$competition, $m1]), ['score_home' => 1, 'score_away' => 0, 'result_version' => 0]);

    $competition->refresh();
    expect($competition->status)->toBe(CompetitionStatus::InProgress);
});

it('recalculates standings after score correction', function () {
    $competition = createLockedCompetition();
    $p1 = Participant::factory()->for($competition)->create();
    $p2 = Participant::factory()->for($competition)->create();
    $p3 = Participant::factory()->for($competition)->create();

    CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'round' => 1, 'sequence' => 1,
        'participant_id_home' => $p1->id, 'participant_id_away' => $p2->id,
        'status' => CompetitionMatchStatus::Ready,
    ]);
    $match = CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'round' => 1, 'sequence' => 2,
        'participant_id_home' => $p1->id, 'participant_id_away' => $p3->id,
        'status' => CompetitionMatchStatus::Ready,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.matches.score.update', [$competition, $match]), ['score_home' => 2, 'score_away' => 2, 'result_version' => 0]);

    $updatedMatch = $match->fresh();
    $this->actingAs($this->admin)
        ->post(route('admin.matches.score.update', [$competition, $updatedMatch]), [
            'score_home' => 3,
            'score_away' => 1,
            'result_version' => $updatedMatch->result_version,
        ]);

    $match->refresh();
    expect($match->score_home)->toBe(3);
    expect($match->score_away)->toBe(1);
    expect($match->result_version)->toBe(2);
});

// C4 — Knockout progression

it('knockout winner advances to next match slot', function () {
    $competition = Competition::factory()->create([
        'format' => CompetitionFormat::Knockout,
        'status' => CompetitionStatus::Locked,
    ]);
    $p1 = Participant::factory()->for($competition)->create();
    $p2 = Participant::factory()->for($competition)->create();
    $p3 = Participant::factory()->for($competition)->create();
    $p4 = Participant::factory()->for($competition)->create();

    $final = CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'round' => 2, 'sequence' => 1,
        'participant_id_home' => null, 'participant_id_away' => null,
        'status' => CompetitionMatchStatus::Pending,
    ]);

    $semi1 = CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'round' => 1, 'sequence' => 1,
        'participant_id_home' => $p1->id, 'participant_id_away' => $p2->id,
        'status' => CompetitionMatchStatus::Ready,
        'next_match_id' => $final->id,
        'next_slot' => 1,
    ]);

    CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'round' => 1, 'sequence' => 2,
        'participant_id_home' => $p3->id, 'participant_id_away' => $p4->id,
        'status' => CompetitionMatchStatus::Ready,
        'next_match_id' => $final->id,
        'next_slot' => 2,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.matches.score.update', [$competition, $semi1]), [
            'score_home' => 2,
            'score_away' => 1,
            'result_version' => 0,
        ]);

    $final->refresh();
    expect($final->participant_id_home)->toBe($p1->id);
    expect($final->status)->toBe(CompetitionMatchStatus::Pending);

    $semi1->refresh();
    expect($semi1->status)->toBe(CompetitionMatchStatus::Completed);
    expect($semi1->winner_id)->toBe($p1->id);
});

it('knockout tie requires winner selection', function () {
    $competition = Competition::factory()->create([
        'format' => CompetitionFormat::Knockout,
        'status' => CompetitionStatus::Locked,
    ]);
    $match = createMatchWithParticipants($competition);

    $this->actingAs($this->admin)
        ->post(route('admin.matches.score.update', [$competition, $match]), [
            'score_home' => 1,
            'score_away' => 1,
            'result_version' => 0,
        ])
        ->assertSessionHasErrors('winner_id');
});

it('knockout tie with valid winner proceeds', function () {
    $competition = Competition::factory()->create([
        'format' => CompetitionFormat::Knockout,
        'status' => CompetitionStatus::Locked,
    ]);
    $p1 = Participant::factory()->for($competition)->create();
    $p2 = Participant::factory()->for($competition)->create();

    $final = CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'round' => 2, 'sequence' => 1,
        'participant_id_home' => null, 'participant_id_away' => null,
        'status' => CompetitionMatchStatus::Pending,
    ]);

    $match = CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'round' => 1, 'sequence' => 1,
        'participant_id_home' => $p1->id, 'participant_id_away' => $p2->id,
        'status' => CompetitionMatchStatus::Ready,
        'next_match_id' => $final->id,
        'next_slot' => 1,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.matches.score.update', [$competition, $match]), [
            'score_home' => 1,
            'score_away' => 1,
            'winner_id' => $p1->id,
            'result_version' => 0,
        ])
        ->assertRedirect();

    $final->refresh();
    expect($final->participant_id_home)->toBe($p1->id);
});

it('knockout final completed finishes competition', function () {
    $competition = Competition::factory()->create([
        'format' => CompetitionFormat::Knockout,
        'status' => CompetitionStatus::Locked,
    ]);
    $p1 = Participant::factory()->for($competition)->create();
    $p2 = Participant::factory()->for($competition)->create();

    $final = CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'round' => 1, 'sequence' => 1,
        'participant_id_home' => $p1->id, 'participant_id_away' => $p2->id,
        'status' => CompetitionMatchStatus::Ready,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.matches.score.update', [$competition, $final]), [
            'score_home' => 2,
            'score_away' => 0,
            'result_version' => 0,
        ]);

    $competition->refresh();
    expect($competition->status)->toBe(CompetitionStatus::Completed);
});

// C5 — Knockout correction

it('knockout score correction without winner change is allowed', function () {
    $competition = Competition::factory()->create([
        'format' => CompetitionFormat::Knockout,
        'status' => CompetitionStatus::InProgress,
    ]);
    $p1 = Participant::factory()->for($competition)->create();
    $p2 = Participant::factory()->for($competition)->create();

    $final = CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'round' => 2, 'sequence' => 1,
        'participant_id_home' => null, 'participant_id_away' => null,
        'status' => CompetitionMatchStatus::Pending,
    ]);

    $match = CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'round' => 1, 'sequence' => 1,
        'participant_id_home' => $p1->id, 'participant_id_away' => $p2->id,
        'score_home' => 2, 'score_away' => 0,
        'winner_id' => $p1->id,
        'status' => CompetitionMatchStatus::Completed,
        'result_version' => 1,
        'next_match_id' => $final->id,
        'next_slot' => 1,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.matches.score.update', [$competition, $match]), [
            'score_home' => 3,
            'score_away' => 0,
            'result_version' => 1,
        ])
        ->assertRedirect();

    $match->refresh();
    expect($match->score_home)->toBe(3);
    expect($match->winner_id)->toBe($p1->id);
});

it('knockout winner change with no downstream completed is allowed', function () {
    $competition = Competition::factory()->create([
        'format' => CompetitionFormat::Knockout,
        'status' => CompetitionStatus::InProgress,
    ]);
    $p1 = Participant::factory()->for($competition)->create();
    $p2 = Participant::factory()->for($competition)->create();
    $p3 = Participant::factory()->for($competition)->create();

    $final = CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'round' => 2, 'sequence' => 1,
        'participant_id_home' => null, 'participant_id_away' => $p3->id,
        'status' => CompetitionMatchStatus::Ready,
    ]);

    $match = CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'round' => 1, 'sequence' => 1,
        'participant_id_home' => $p1->id, 'participant_id_away' => $p2->id,
        'score_home' => 2, 'score_away' => 0,
        'winner_id' => $p1->id,
        'status' => CompetitionMatchStatus::Completed,
        'result_version' => 1,
        'next_match_id' => $final->id,
        'next_slot' => 1,
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.matches.score.update', [$competition, $match]), [
            'score_home' => 0,
            'score_away' => 1,
            'result_version' => 1,
        ])
        ->assertRedirect();

    $match->refresh();
    expect($match->winner_id)->toBe($p2->id);

    $final->refresh();
    expect($final->participant_id_home)->toBe($p2->id);
});

it('knockout winner change with completed downstream is rejected', function () {
    $competition = Competition::factory()->create([
        'format' => CompetitionFormat::Knockout,
        'status' => CompetitionStatus::InProgress,
    ]);
    $p1 = Participant::factory()->for($competition)->create();
    $p2 = Participant::factory()->for($competition)->create();
    $p3 = Participant::factory()->for($competition)->create();

    $final = CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'round' => 2, 'sequence' => 1,
        'participant_id_home' => $p1->id, 'participant_id_away' => $p3->id,
        'score_home' => 1, 'score_away' => 0,
        'winner_id' => $p1->id,
        'status' => CompetitionMatchStatus::Completed,
        'result_version' => 1,
    ]);

    $match = CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'round' => 1, 'sequence' => 1,
        'participant_id_home' => $p1->id, 'participant_id_away' => $p2->id,
        'score_home' => 2, 'score_away' => 0,
        'winner_id' => $p1->id,
        'status' => CompetitionMatchStatus::Completed,
        'result_version' => 1,
        'next_match_id' => $final->id,
        'next_slot' => 1,
    ]);

    $response = $this->actingAs($this->admin)
        ->post(route('admin.matches.score.update', [$competition, $match]), [
            'score_home' => 0,
            'score_away' => 1,
            'result_version' => 1,
        ]);

    $response->assertStatus(422);

    $match->refresh();
    expect($match->score_home)->toBe(2);
    expect($match->winner_id)->toBe($p1->id);
});

it('admin can lock and unlock final results on completed competition', function () {
    $competition = Competition::factory()->completed()->create();
    $match = createMatchWithParticipants($competition);

    $this->actingAs($this->admin)
        ->post(route('admin.competitions.lock-results', $competition))
        ->assertRedirect();

    $competition->refresh();
    expect($competition->is_results_locked)->toBeTrue();
    expect($competition->results_locked_by)->toBe($this->admin->id);

    $this->actingAs($this->admin)
        ->post(route('admin.competitions.lock-results', $competition))
        ->assertRedirect();

    $competition->refresh();
    expect($competition->is_results_locked)->toBeFalse();
    expect($competition->results_locked_by)->toBeNull();
});

it('cannot lock final results if competition is not completed', function () {
    $competition = createLockedCompetition();
    $match = createMatchWithParticipants($competition);

    $this->actingAs($this->admin)
        ->post(route('admin.competitions.lock-results', $competition))
        ->assertSessionHasErrors('competition');

    $competition->refresh();
    expect($competition->is_results_locked)->toBeFalse();
});

it('operator cannot lock results', function () {
    $competition = Competition::factory()->completed()->create();
    attachOperator($competition, $this->operator);

    $this->actingAs($this->operator)
        ->post(route('admin.competitions.lock-results', $competition))
        ->assertForbidden();
});

it('score update is rejected when results are locked', function () {
    $competition = Competition::factory()->resultsLocked()->create();
    $match = createMatchWithParticipants($competition);
    attachOperator($competition, $this->operator);

    $this->actingAs($this->admin)
        ->post(route('admin.matches.score.update', [$competition, $match]), [
            'score_home' => 2,
            'score_away' => 1,
            'result_version' => 0,
        ])
        ->assertSessionHasErrors('competition');

    $this->actingAs($this->operator)
        ->post(route('operator.matches.score.update', [$competition, $match]), [
            'score_home' => 2,
            'score_away' => 1,
            'result_version' => 0,
        ])
        ->assertSessionHasErrors('competition');
});
