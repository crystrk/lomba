<?php

use App\Enums\CompetitionFormat;
use App\Enums\CompetitionStatus;
use App\Enums\UserRole;
use App\Models\Competition;
use App\Models\Participant;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
});

it('generates draw for 64 participants in full competition', function () {
    $competition = Competition::factory()->create([
        'format' => CompetitionFormat::FullCompetition,
        'status' => CompetitionStatus::Draft,
    ]);

    Participant::factory()->count(64)->create([
        'competition_id' => $competition->id,
    ]);

    actingAs($this->admin)
        ->post(route('admin.competitions.shuffle', $competition))
        ->assertSessionHasNoErrors();

    $competition->refresh();

    expect($competition->status)->toBe(CompetitionStatus::Drawn);
    expect($competition->participants()->count())->toBe(64);
    expect($competition->matches()->count())->toBeGreaterThan(0);
});

it('generates draw for 64 participants in knockout', function () {
    $competition = Competition::factory()->create([
        'format' => CompetitionFormat::Knockout,
        'status' => CompetitionStatus::Draft,
    ]);

    Participant::factory()->count(64)->create([
        'competition_id' => $competition->id,
    ]);

    actingAs($this->admin)
        ->post(route('admin.competitions.shuffle', $competition))
        ->assertSessionHasNoErrors();

    $competition->refresh();

    expect($competition->status)->toBe(CompetitionStatus::Drawn);
    expect($competition->participants()->count())->toBe(64);
    expect($competition->matches()->count())->toBeGreaterThan(0);
});

it('generates draw for 64 participants in half competition', function () {
    $competition = Competition::factory()->create([
        'format' => CompetitionFormat::HalfCompetition,
        'status' => CompetitionStatus::Draft,
    ]);

    Participant::factory()->count(64)->create([
        'competition_id' => $competition->id,
    ]);

    actingAs($this->admin)
        ->post(route('admin.competitions.shuffle', $competition))
        ->assertSessionHasNoErrors();

    $competition->refresh();

    expect($competition->status)->toBe(CompetitionStatus::Drawn);
    expect($competition->participants()->count())->toBe(64);
    expect($competition->matches()->count())->toBeGreaterThan(0);
});

it('serves landing page quickly with many competitions', function () {
    Competition::factory()->count(10)->create(['status' => CompetitionStatus::Locked]);

    $start = microtime(true);
    $response = $this->get(route('home'));
    $duration = (microtime(true) - $start) * 1000;

    $response->assertOk();
    expect($duration)->toBeLessThan(1000);
});

it('serves admin competition list with many records', function () {
    Competition::factory()->count(25)->create();

    actingAs($this->admin);

    $start = microtime(true);
    $response = $this->get(route('admin.competitions.index'));
    $duration = (microtime(true) - $start) * 1000;

    $response->assertOk();
    expect($duration)->toBeLessThan(1000);
});
