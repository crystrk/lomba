<?php

use App\Enums\CompetitionFormat;
use App\Enums\CompetitionMatchStatus;
use App\Enums\CompetitionStatus;
use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\CompetitionMatch;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

it('has user enum cast and columns', function () {
    $user = User::factory()->create([
        'role' => UserRole::Admin,
        'is_active' => true,
    ]);

    expect($user->role)->toBeInstanceOf(UserRole::class)
        ->and($user->role->value)->toBe('admin')
        ->and($user->is_active)->toBeTrue()
        ->and($user->isAdmin())->toBeTrue()
        ->and($user->isOperator())->toBeFalse();
});

it('creates operator user correctly', function () {
    $user = User::factory()->operator()->create();

    expect($user->role)->toBe(UserRole::Operator)
        ->and($user->isOperator())->toBeTrue()
        ->and($user->isAdmin())->toBeFalse();
});

it('creates inactive user', function () {
    $user = User::factory()->inactive()->create();

    expect($user->is_active)->toBeFalse();
});

it('has competition enum casts and defaults', function () {
    $competition = Competition::factory()->create();

    expect($competition->format)->toBeInstanceOf(CompetitionFormat::class)
        ->and($competition->status)->toBeInstanceOf(CompetitionStatus::class)
        ->and($competition->status->value)->toBe('draft')
        ->and($competition->isDraft())->toBeTrue()
        ->and($competition->isEditable())->toBeTrue()
        ->and($competition->isActive())->toBeFalse()
        ->and($competition->usesPoints())->toBeTrue();
});

it('creates knockout competition without points', function () {
    $competition = Competition::factory()->knockout()->create();

    expect($competition->format->value)->toBe('knockout')
        ->and($competition->win_points)->toBeNull()
        ->and($competition->usesPoints())->toBeFalse()
        ->and($competition->isKnockout())->toBeTrue();
});

it('creates competition in all status states', function () {
    $draft = Competition::factory()->draft()->create();
    $drawn = Competition::factory()->drawn()->create();
    $locked = Competition::factory()->locked()->create();
    $inProgress = Competition::factory()->inProgress()->create();
    $completed = Competition::factory()->completed()->create();

    expect($draft->status->value)->toBe('draft')
        ->and($drawn->status->value)->toBe('drawn')
        ->and($locked->status->value)->toBe('locked')
        ->and($inProgress->status->value)->toBe('in_progress')
        ->and($completed->status->value)->toBe('completed');

    expect($draft->isActive())->toBeFalse()
        ->and($locked->isActive())->toBeTrue()
        ->and($inProgress->isActive())->toBeTrue();
});

it('has participant with normalized name', function () {
    $competition = Competition::factory()->create();
    $participant = Participant::factory()->create([
        'competition_id' => $competition->id,
        'name' => 'Tim ABC',
    ]);

    expect($participant->normalized_name)->toBe('tim abc')
        ->and($participant->competition_id)->toBe($competition->id);
});

it('enforces unique participant name per competition', function () {
    $competition = Competition::factory()->create();
    Participant::factory()->create([
        'competition_id' => $competition->id,
        'name' => 'Tim ABC',
        'normalized_name' => 'tim abc',
    ]);

    expect(fn () => Participant::factory()->create([
        'competition_id' => $competition->id,
        'name' => 'Tim ABC',
        'normalized_name' => 'tim abc',
    ]))->toThrow(QueryException::class);
});

it('allows same participant name in different competitions', function () {
    $comp1 = Competition::factory()->create();
    $comp2 = Competition::factory()->create();

    Participant::factory()->create([
        'competition_id' => $comp1->id,
        'name' => 'Tim ABC',
        'normalized_name' => 'tim abc',
    ]);

    Participant::factory()->create([
        'competition_id' => $comp2->id,
        'name' => 'Tim ABC',
        'normalized_name' => 'tim abc',
    ]);

    expect(Participant::count())->toBe(2);
});

it('has competition match with status enum', function () {
    $match = CompetitionMatch::factory()->create();

    expect($match->status)->toBeInstanceOf(CompetitionMatchStatus::class);
});

it('cascades delete from competition to participants and matches', function () {
    $competition = Competition::factory()->create();
    $participant = Participant::factory()->create(['competition_id' => $competition->id]);
    CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'participant_id_home' => $participant->id,
        'participant_id_away' => $participant->id,
    ]);

    $competition->delete();

    expect(Participant::where('competition_id', $competition->id)->count())->toBe(0)
        ->and(CompetitionMatch::where('competition_id', $competition->id)->count())->toBe(0);
});

it('has competition operator relation', function () {
    $admin = User::factory()->admin()->create();
    $operator = User::factory()->operator()->create();
    $competition = Competition::factory()->create();

    $competition->operators()->attach($operator->id, [
        'assigned_by' => $admin->id,
        'assigned_at' => now(),
    ]);

    expect($competition->operators)->toHaveCount(1)
        ->and($operator->assignedCompetitions)->toHaveCount(1)
        ->and($operator->assignedCompetitions->first()->id)->toBe($competition->id);
});

it('has competition locked relation', function () {
    $admin = User::factory()->admin()->create();
    $competition = Competition::factory()->locked()->create([
        'locked_by' => $admin->id,
    ]);

    expect($competition->locker->id)->toBe($admin->id);
});

it('has competition match relations', function () {
    $home = Participant::factory()->create();
    $away = Participant::factory()->create();
    $match = CompetitionMatch::factory()->create([
        'participant_id_home' => $home->id,
        'participant_id_away' => $away->id,
    ]);

    expect($match->homeParticipant->id)->toBe($home->id)
        ->and($match->awayParticipant->id)->toBe($away->id);
});

it('has competition matches table columns', function () {
    $columns = Schema::getColumnListing('competition_matches');

    expect($columns)->toContain('id', 'competition_id', 'round', 'leg', 'sequence')
        ->toContain('participant_id_home', 'participant_id_away')
        ->toContain('score_home', 'score_away')
        ->toContain('winner_id', 'status')
        ->toContain('next_match_id', 'next_slot')
        ->toContain('result_version', 'result_updated_by', 'result_updated_at');
});
