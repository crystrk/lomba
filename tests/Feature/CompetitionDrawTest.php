<?php

use App\Enums\CompetitionFormat;
use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Models\Participant;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->operator = User::factory()->operator()->create();
    $this->withoutVite();
});

it('admin can view draw page', function () {
    $competition = Competition::factory()
        ->draft()
        ->has(Participant::factory()->count(4))
        ->create();

    $this->actingAs($this->admin)
        ->get(route('admin.competitions.draw.show', $competition))
        ->assertOk();
});

it('operator cannot view draw page', function () {
    $competition = Competition::factory()
        ->draft()
        ->has(Participant::factory()->count(4))
        ->create();

    $this->actingAs($this->operator)
        ->get(route('admin.competitions.draw.show', $competition))
        ->assertForbidden();
});

it('guest cannot view draw page', function () {
    $competition = Competition::factory()
        ->draft()
        ->has(Participant::factory()->count(4))
        ->create();

    $this->get(route('admin.competitions.draw.show', $competition))
        ->assertRedirect(route('login'));
});

it('shuffle creates matches and sets status to drawn', function () {
    $competition = Competition::factory()
        ->draft()
        ->has(Participant::factory()->count(4))
        ->create();

    $this->actingAs($this->admin)
        ->post(route('admin.competitions.shuffle', $competition))
        ->assertRedirect();

    $competition->refresh();

    expect($competition->status)->toBe(CompetitionStatus::Drawn)
        ->and($competition->draw_version)->toBe(1)
        ->and($competition->matches()->count())->toBeGreaterThan(0);
});

it('shuffle sets draw_position on participants', function () {
    $competition = Competition::factory()
        ->draft()
        ->has(Participant::factory()->count(4))
        ->create();

    $this->actingAs($this->admin)
        ->post(route('admin.competitions.shuffle', $competition));

    $positions = $competition->participants()->pluck('draw_position')->filter()->values();

    expect($positions->count())->toBe(4)
        ->and($positions->unique()->count())->toBe(4)
        ->and($positions->min())->toBe(1)
        ->and($positions->max())->toBe(4);
});

it('re-shuffle replaces old matches', function () {
    $competition = Competition::factory()
        ->draft()
        ->has(Participant::factory()->count(4))
        ->create();

    $this->actingAs($this->admin)
        ->post(route('admin.competitions.shuffle', $competition));

    $firstMatches = $competition->matches()->pluck('id')->toArray();
    $firstVersion = $competition->fresh()->draw_version;

    $this->actingAs($this->admin)
        ->post(route('admin.competitions.shuffle', $competition));

    $currentMatches = $competition->matches()->pluck('id')->toArray();

    expect($competition->fresh()->draw_version)->toBe($firstVersion + 1);

    $common = array_intersect($firstMatches, $currentMatches);
    expect($common)->toBeEmpty();
});

it('shuffle fails with less than 2 participants', function () {
    $competition = Competition::factory()
        ->draft()
        ->has(Participant::factory()->count(1))
        ->create();

    $this->actingAs($this->admin)
        ->post(route('admin.competitions.shuffle', $competition))
        ->assertStatus(422);
});

it('shuffle fails when competition is locked', function () {
    $competition = Competition::factory()
        ->locked()
        ->has(Participant::factory()->count(4))
        ->create();

    $this->actingAs($this->admin)
        ->post(route('admin.competitions.shuffle', $competition))
        ->assertForbidden();
});

it('shuffle fails when competition is in progress', function () {
    $competition = Competition::factory()
        ->inProgress()
        ->has(Participant::factory()->count(4))
        ->create();

    $this->actingAs($this->admin)
        ->post(route('admin.competitions.shuffle', $competition))
        ->assertForbidden();
});

it('shuffle generates correct match count for half competition', function () {
    $competition = Competition::factory()
        ->draft()
        ->create(['format' => CompetitionFormat::HalfCompetition]);
    Participant::factory()->count(4)->create(['competition_id' => $competition->id]);

    $this->actingAs($this->admin)
        ->post(route('admin.competitions.shuffle', $competition))
        ->assertRedirect();

    expect($competition->fresh()->matches()->count())->toBe(6);
});

it('shuffle generates correct match count for full competition', function () {
    $competition = Competition::factory()
        ->draft()
        ->create(['format' => CompetitionFormat::FullCompetition]);
    Participant::factory()->count(4)->create(['competition_id' => $competition->id]);

    $this->actingAs($this->admin)
        ->post(route('admin.competitions.shuffle', $competition))
        ->assertRedirect();

    expect($competition->fresh()->matches()->count())->toBe(12);
});

it('shuffle generates correct match count for knockout', function () {
    $competition = Competition::factory()
        ->draft()
        ->create(['format' => CompetitionFormat::Knockout]);
    Participant::factory()->count(6)->create(['competition_id' => $competition->id]);

    $this->actingAs($this->admin)
        ->post(route('admin.competitions.shuffle', $competition))
        ->assertRedirect();

    $matchCount = $competition->fresh()->matches()->count();
    expect($matchCount)->toBeGreaterThanOrEqual(6);
});

it('matches have unique positions per competition', function () {
    $competition = Competition::factory()
        ->draft()
        ->has(Participant::factory()->count(4))
        ->create();

    $this->actingAs($this->admin)
        ->post(route('admin.competitions.shuffle', $competition));

    $matches = $competition->matches()->get();
    $positionKeys = $matches->map(fn ($m) => "{$m->round}-{$m->leg}-{$m->sequence}");

    expect($positionKeys->count())->toBe($positionKeys->unique()->count());
});

it('operator cannot shuffle', function () {
    $competition = Competition::factory()
        ->draft()
        ->has(Participant::factory()->count(4))
        ->create();

    $this->actingAs($this->operator)
        ->post(route('admin.competitions.shuffle', $competition))
        ->assertForbidden();
});

it('participant mutation resets draw and reverts to draft', function () {
    $competition = Competition::factory()
        ->draft()
        ->has(Participant::factory()->count(4))
        ->create();

    $this->actingAs($this->admin)
        ->post(route('admin.competitions.shuffle', $competition));

    expect($competition->fresh()->status)->toBe(CompetitionStatus::Drawn);

    $this->actingAs($this->admin)
        ->post(route('admin.competitions.participants.store', $competition), [
            'name' => 'Tim Baru',
        ]);

    $competition->refresh();

    expect($competition->status)->toBe(CompetitionStatus::Draft)
        ->and($competition->matches()->count())->toBe(0);
});

it('match preview is consistent on reload', function () {
    $competition = Competition::factory()
        ->draft()
        ->has(Participant::factory()->count(4))
        ->create();

    $this->actingAs($this->admin)
        ->post(route('admin.competitions.shuffle', $competition));

    $firstView = $this->actingAs($this->admin)
        ->get(route('admin.competitions.draw.show', $competition))
        ->assertOk();

    $secondView = $this->actingAs($this->admin)
        ->get(route('admin.competitions.draw.show', $competition))
        ->assertOk();
});

it('lock sets status to locked', function () {
    $competition = Competition::factory()
        ->draft()
        ->has(Participant::factory()->count(4))
        ->create();

    $this->actingAs($this->admin)
        ->post(route('admin.competitions.shuffle', $competition))
        ->assertRedirect();

    $competition->refresh();

    $this->actingAs($this->admin)
        ->post(route('admin.competitions.lock', $competition), [
            'draw_version' => $competition->draw_version,
        ])
        ->assertRedirect();

    $competition->refresh();

    expect($competition->status)->toBe(CompetitionStatus::Locked)
        ->and($competition->locked_by)->toBe($this->admin->id)
        ->and($competition->locked_at)->not->toBeNull();
});

it('lock fails with wrong draw_version', function () {
    $competition = Competition::factory()
        ->draft()
        ->has(Participant::factory()->count(4))
        ->create();

    $this->actingAs($this->admin)
        ->post(route('admin.competitions.shuffle', $competition));

    $this->actingAs($this->admin)
        ->post(route('admin.competitions.lock', $competition), [
            'draw_version' => 999,
        ])
        ->assertInvalid(['draw_version']);
});

it('lock fails on draft competition', function () {
    $competition = Competition::factory()
        ->draft()
        ->has(Participant::factory()->count(4))
        ->create();

    $this->actingAs($this->admin)
        ->post(route('admin.competitions.lock', $competition), [
            'draw_version' => 0,
        ])
        ->assertStatus(422);
});

it('operator cannot lock', function () {
    $competition = Competition::factory()
        ->draft()
        ->has(Participant::factory()->count(4))
        ->create();

    $this->actingAs($this->admin)
        ->post(route('admin.competitions.shuffle', $competition));

    $this->actingAs($this->operator)
        ->post(route('admin.competitions.lock', $competition), [
            'draw_version' => 1,
        ])
        ->assertForbidden();
});

it('shuffle after lock is forbidden', function () {
    $competition = Competition::factory()
        ->draft()
        ->has(Participant::factory()->count(4))
        ->create();

    $this->actingAs($this->admin)
        ->post(route('admin.competitions.shuffle', $competition));

    $competition->refresh();
    $this->actingAs($this->admin)
        ->post(route('admin.competitions.lock', $competition), [
            'draw_version' => $competition->draw_version,
        ]);

    $this->actingAs($this->admin)
        ->post(route('admin.competitions.shuffle', $competition))
        ->assertForbidden();
});
