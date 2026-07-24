<?php

use App\Enums\CompetitionFormat;
use App\Enums\CompetitionMatchStatus;
use App\Enums\CompetitionSport;
use App\Enums\CompetitionStatus;
use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\CompetitionMatch;
use App\Models\Participant;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->withoutVite();
});

// C1 — Public routes

it('guest can access landing', function () {
    $this->get(route('home'))->assertSuccessful();
});

it('landing shows active and completed competitions (locked, in_progress, completed)', function () {
    Competition::factory()->create(['name' => 'Draft', 'status' => CompetitionStatus::Draft]);
    Competition::factory()->create(['name' => 'Drawn', 'status' => CompetitionStatus::Drawn]);
    $locked = Competition::factory()->create(['name' => 'Locked', 'status' => CompetitionStatus::Locked]);
    $inProgress = Competition::factory()->create(['name' => 'In Progress', 'status' => CompetitionStatus::InProgress]);
    $completed = Competition::factory()->create(['name' => 'Completed', 'status' => CompetitionStatus::Completed]);

    $response = $this->get(route('home'));

    $response->assertSuccessful();
    $props = $response->inertiaProps();

    expect($props['competitions'])->toHaveCount(3);
    expect(collect($props['competitions'])->pluck('name')->sort()->values()->toArray())->toBe(['Completed', 'In Progress', 'Locked']);
});

it('landing includes competition sport and safely returns null for legacy competitions', function () {
    Competition::factory()->locked()->create(['name' => 'Badminton', 'sport' => CompetitionSport::Badminton]);
    Competition::factory()->locked()->create(['name' => 'Legacy', 'sport' => null]);

    $competitions = collect($this->get(route('home'))->inertiaProps()['competitions'])->keyBy('name');

    expect($competitions['Badminton']['sport'])->toBe(CompetitionSport::Badminton->value)
        ->and($competitions['Legacy']['sport'])->toBeNull();
});

it('completed competition appears on landing and is accessible via slug', function () {
    $competition = Competition::factory()->create([
        'name' => 'Past Event',
        'slug' => 'past-event',
        'status' => CompetitionStatus::Completed,
    ]);

    $landingResponse = $this->get(route('home'));
    expect($landingResponse->inertiaProps()['competitions'])->toHaveCount(1);

    $this->get(route('public.competitions.show', $competition))
        ->assertSuccessful();
});

it('draft competition returns 404 for guest', function () {
    $competition = Competition::factory()->create([
        'status' => CompetitionStatus::Draft,
    ]);

    $this->get(route('public.competitions.show', $competition))->assertNotFound();
});

it('drawn competition returns 404 for guest', function () {
    $competition = Competition::factory()->create([
        'status' => CompetitionStatus::Drawn,
    ]);

    $this->get(route('public.competitions.show', $competition))->assertNotFound();
});

it('invalid slug returns 404', function () {
    $this->get('/lomba/nonexistent-slug')->assertNotFound();
});

// C3 — Competition format detail

it('public competition detail shows standings for competition format', function () {
    $competition = Competition::factory()->create([
        'format' => CompetitionFormat::HalfCompetition,
        'status' => CompetitionStatus::InProgress,
        'win_points' => 3, 'draw_points' => 1, 'loss_points' => 0,
    ]);
    $p1 = Participant::factory()->for($competition)->create(['name' => 'Tim A']);
    $p2 = Participant::factory()->for($competition)->create(['name' => 'Tim B']);

    CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'participant_id_home' => $p1->id, 'participant_id_away' => $p2->id,
        'score_home' => 2, 'score_away' => 1,
        'status' => CompetitionMatchStatus::Completed,
    ]);

    $response = $this->get(route('public.competitions.show', $competition));
    $props = $response->inertiaProps();

    expect($props['standings'])->toHaveCount(2);
    expect($props['standings'][0]['participant_name'])->toBe('Tim A');
    expect($props['standings'][0]['points'])->toBe(3);
});

it('full competition displays leg info in props', function () {
    $competition = Competition::factory()->create([
        'format' => CompetitionFormat::FullCompetition,
        'status' => CompetitionStatus::InProgress,
    ]);
    $p = Participant::factory()->count(2)->for($competition)->create();

    CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'round' => 1, 'leg' => 1, 'sequence' => 1,
        'participant_id_home' => $p[0]->id, 'participant_id_away' => $p[1]->id,
        'score_home' => 1, 'score_away' => 1,
        'status' => CompetitionMatchStatus::Completed,
    ]);
    CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'round' => 1, 'leg' => 2, 'sequence' => 1,
        'participant_id_home' => $p[1]->id, 'participant_id_away' => $p[0]->id,
        'status' => CompetitionMatchStatus::Ready,
    ]);

    $response = $this->get(route('public.competitions.show', $competition));
    $props = $response->inertiaProps();

    $matchLegs = collect($props['matchesByRound']['1'])->pluck('leg')->unique()->sort()->values();
    expect($matchLegs->toArray())->toBe([1, 2]);
});

it('pending and completed matches are distinguishable in props', function () {
    $competition = Competition::factory()->create([
        'format' => CompetitionFormat::HalfCompetition,
        'status' => CompetitionStatus::InProgress,
    ]);
    $p = Participant::factory()->count(2)->for($competition)->create();

    CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'round' => 1, 'sequence' => 1,
        'participant_id_home' => $p[0]->id, 'participant_id_away' => $p[1]->id,
        'score_home' => 1, 'score_away' => 0,
        'status' => CompetitionMatchStatus::Completed,
    ]);

    CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'round' => 2, 'sequence' => 1,
        'participant_id_home' => $p[0]->id, 'participant_id_away' => $p[1]->id,
        'status' => CompetitionMatchStatus::Ready,
    ]);

    $response = $this->get(route('public.competitions.show', $competition));
    $props = $response->inertiaProps();

    $completedStatuses = collect($props['matchesByRound']['1'])->pluck('status')->all();
    $pendingStatuses = collect($props['matchesByRound']['2'])->pluck('status')->all();

    expect($completedStatuses)->toContain('completed');
    expect($pendingStatuses)->toContain('ready');
});

it('public props do not include internal data', function () {
    $competition = Competition::factory()->create([
        'format' => CompetitionFormat::HalfCompetition,
        'status' => CompetitionStatus::Locked,
    ]);
    $p = Participant::factory()->count(2)->for($competition)->create();

    CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'participant_id_home' => $p[0]->id, 'participant_id_away' => $p[1]->id,
        'status' => CompetitionMatchStatus::Ready,
    ]);

    $response = $this->get(route('public.competitions.show', $competition));
    $propsJson = json_encode($response->inertiaProps());

    expect($propsJson)->not->toContain($this->admin->email);
    expect($propsJson)->not->toContain('draw_version');
    expect($propsJson)->not->toContain('result_version');
    expect($propsJson)->not->toContain('locked_by');
});

// C4 — Knockout bracket

it('knockout bracket displays rounds with matches', function () {
    $competition = Competition::factory()->create([
        'format' => CompetitionFormat::Knockout,
        'status' => CompetitionStatus::InProgress,
    ]);
    $p = Participant::factory()->count(4)->for($competition)->create();

    CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'round' => 2, 'sequence' => 1,
        'status' => CompetitionMatchStatus::Pending,
    ]);

    CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'round' => 1, 'sequence' => 1,
        'participant_id_home' => $p[0]->id, 'participant_id_away' => $p[1]->id,
        'score_home' => 2, 'score_away' => 0,
        'winner_id' => $p[0]->id,
        'status' => CompetitionMatchStatus::Completed,
        'next_match_id' => 1, 'next_slot' => 1,
    ]);

    CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'round' => 1, 'sequence' => 2,
        'participant_id_home' => $p[2]->id, 'participant_id_away' => $p[3]->id,
        'status' => CompetitionMatchStatus::Ready,
        'next_match_id' => 1, 'next_slot' => 2,
    ]);

    $response = $this->get(route('public.competitions.show', $competition));
    $props = $response->inertiaProps();

    expect($props['matchesByRound'])->toHaveKeys(['1', '2']);
    expect($props['matchesByRound']['1'])->toHaveCount(2);
    expect($props['matchesByRound']['2'])->toHaveCount(1);
});

it('bye match appears in props with bye status', function () {
    $competition = Competition::factory()->create([
        'format' => CompetitionFormat::Knockout,
        'status' => CompetitionStatus::InProgress,
    ]);
    $p = Participant::factory()->for($competition)->create();

    CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'round' => 2, 'sequence' => 1,
        'status' => CompetitionMatchStatus::Pending,
    ]);

    CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'round' => 1, 'sequence' => 1,
        'participant_id_home' => $p->id, 'participant_id_away' => null,
        'status' => CompetitionMatchStatus::Bye,
        'next_match_id' => 1, 'next_slot' => 1,
    ]);

    $response = $this->get(route('public.competitions.show', $competition));
    $props = $response->inertiaProps();

    expect($props['matchesByRound']['1'][0]['status'])->toBe('bye');
});

it('future knockout slot has null home and away', function () {
    $competition = Competition::factory()->create([
        'format' => CompetitionFormat::Knockout,
        'status' => CompetitionStatus::Locked,
    ]);
    $p = Participant::factory()->count(4)->for($competition)->create();

    CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'round' => 2, 'sequence' => 1,
        'participant_id_home' => null, 'participant_id_away' => null,
        'status' => CompetitionMatchStatus::Pending,
    ]);

    CompetitionMatch::factory()->create([
        'competition_id' => $competition->id,
        'round' => 1, 'sequence' => 1,
        'participant_id_home' => $p[0]->id, 'participant_id_away' => $p[1]->id,
        'status' => CompetitionMatchStatus::Ready,
        'next_match_id' => 1, 'next_slot' => 1,
    ]);

    $response = $this->get(route('public.competitions.show', $competition));
    $props = $response->inertiaProps();

    expect($props['matchesByRound']['2'][0]['home'])->toBeNull();
    expect($props['matchesByRound']['2'][0]['away'])->toBeNull();
});

it('lock and in_progress competition visible to guest (AC-15)', function () {
    $locked = Competition::factory()->create([
        'name' => 'Locked Comp',
        'status' => CompetitionStatus::Locked,
    ]);
    $inProgress = Competition::factory()->create([
        'name' => 'InProgress Comp',
        'status' => CompetitionStatus::InProgress,
    ]);

    $this->get(route('public.competitions.show', $locked))->assertSuccessful();
    $this->get(route('public.competitions.show', $inProgress))->assertSuccessful();
});
