<?php

use App\Enums\CompetitionFormat;
use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->operator = User::factory()->operator()->create();
});

it('only admin can view competition list', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.competitions.index'))
        ->assertOk();

    $this->actingAs($this->operator)
        ->get(route('admin.competitions.index'))
        ->assertForbidden();
});

it('only admin can view create competition form', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.competitions.create'))
        ->assertOk();

    $this->actingAs($this->operator)
        ->get(route('admin.competitions.create'))
        ->assertForbidden();
});

it('admin can create half competition', function () {
    $this->actingAs($this->admin);

    $response = $this->post(route('admin.competitions.store'), [
        'name' => 'Kejuaraan Bulutangkis',
        'description' => 'Pertandingan tahunan',
        'format' => 'half_competition',
        'starts_at' => '2026-08-01',
        'ends_at' => '2026-08-10',
        'win_points' => 3,
        'draw_points' => 1,
        'loss_points' => 0,
    ]);

    $response->assertRedirect(route('admin.competitions.index'));

    $competition = Competition::where('name', 'Kejuaraan Bulutangkis')->first();

    expect($competition)->not->toBeNull()
        ->and($competition->format)->toBe(CompetitionFormat::HalfCompetition)
        ->and($competition->status)->toBe(CompetitionStatus::Draft)
        ->and($competition->win_points)->toBe(3)
        ->and($competition->draw_points)->toBe(1)
        ->and($competition->loss_points)->toBe(0)
        ->and($competition->draw_version)->toBe(0)
        ->and($competition->slug)->not->toBeNull();
});

it('admin can create knockout competition', function () {
    $this->actingAs($this->admin);

    $response = $this->post(route('admin.competitions.store'), [
        'name' => 'Turnamen Tenis',
        'format' => 'knockout',
    ]);

    $response->assertRedirect(route('admin.competitions.index'));

    $competition = Competition::where('name', 'Turnamen Tenis')->first();

    expect($competition)->not->toBeNull()
        ->and($competition->format)->toBe(CompetitionFormat::Knockout)
        ->and($competition->win_points)->toBeNull()
        ->and($competition->draw_points)->toBeNull()
        ->and($competition->loss_points)->toBeNull();
});

it('admin can create full competition with negative points', function () {
    $this->actingAs($this->admin);

    $this->post(route('admin.competitions.store'), [
        'name' => 'Lomba Mancing',
        'format' => 'full_competition',
        'win_points' => 2,
        'draw_points' => 0,
        'loss_points' => -1,
    ])->assertRedirect(route('admin.competitions.index'));

    $competition = Competition::where('name', 'Lomba Mancing')->first();

    expect($competition->loss_points)->toBe(-1);
});

it('knockout format rejects point values', function () {
    $this->actingAs($this->admin);

    $this->post(route('admin.competitions.store'), [
        'name' => 'Bad Knockout',
        'format' => 'knockout',
        'win_points' => 3,
    ])->assertInvalid(['win_points']);
});

it('half competition requires all point fields', function () {
    $this->actingAs($this->admin);

    $this->post(route('admin.competitions.store'), [
        'name' => 'Incomplete',
        'format' => 'half_competition',
    ])->assertInvalid(['win_points', 'draw_points', 'loss_points']);
});

it('generates unique slug for similar names', function () {
    $this->actingAs($this->admin);

    $this->post(route('admin.competitions.store'), [
        'name' => 'Kejuaraan',
        'format' => 'knockout',
    ])->assertRedirect();

    $this->post(route('admin.competitions.store'), [
        'name' => 'Kejuaraan',
        'format' => 'knockout',
    ])->assertRedirect();

    $slugs = Competition::query()->pluck('slug');

    expect($slugs->unique()->count())->toBe($slugs->count());
});

it('operator cannot create competition', function () {
    $this->actingAs($this->operator)
        ->post(route('admin.competitions.store'), [
            'name' => 'Hacked',
            'format' => 'knockout',
        ])
        ->assertForbidden();
});

it('guest cannot access competition management', function () {
    $this->get(route('admin.competitions.index'))->assertRedirect(route('login'));
    $this->get(route('admin.competitions.create'))->assertRedirect(route('login'));
});

it('admin can view single competition', function () {
    $competition = Competition::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.competitions.show', $competition))
        ->assertOk();
});

it('admin can view edit competition form', function () {
    $competition = Competition::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.competitions.edit', $competition))
        ->assertOk();

    $this->actingAs($this->operator)
        ->get(route('admin.competitions.edit', $competition))
        ->assertForbidden();
});

it('admin can update draft competition', function () {
    $competition = Competition::factory()->draft()->create();

    $this->actingAs($this->admin)
        ->put(route('admin.competitions.update', $competition), [
            'name' => 'Updated Name',
        ])
        ->assertRedirect(route('admin.competitions.index'));

    expect($competition->fresh()->name)->toBe('Updated Name');
});

it('admin can delete draft competition', function () {
    $competition = Competition::factory()->draft()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.competitions.destroy', $competition))
        ->assertRedirect(route('admin.competitions.index'));

    expect(Competition::find($competition->id))->toBeNull();
});

it('cannot update locked competition', function () {
    $competition = Competition::factory()->locked()->create();

    $this->actingAs($this->admin)
        ->put(route('admin.competitions.update', $competition), [
            'name' => 'Hacked Name',
        ])
        ->assertForbidden();
});

it('cannot delete locked competition', function () {
    $competition = Competition::factory()->locked()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.competitions.destroy', $competition))
        ->assertForbidden();
});

it('validates required fields', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.competitions.store'), [])
        ->assertInvalid(['name', 'format']);
});
