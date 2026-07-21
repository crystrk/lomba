<?php

use App\Models\Competition;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->operator = User::factory()->operator()->create();
    $this->anotherOperator = User::factory()->operator()->create();
    $this->competition = Competition::factory()->create();
});

it('admin can view operator assignment page', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.competitions.operators', $this->competition))
        ->assertOk();
});

it('admin can sync operators to competition', function () {
    $this->actingAs($this->admin);

    $this->put(route('admin.competitions.operators.sync', $this->competition), [
        'operator_ids' => [$this->operator->id, $this->anotherOperator->id],
    ])->assertRedirect(route('admin.competitions.operators', $this->competition));

    $this->competition->load('operators');

    expect($this->competition->operators)->toHaveCount(2);
});

it('admin can remove operator from competition', function () {
    $this->competition->operators()->attach($this->operator->id, [
        'assigned_by' => $this->admin->id,
        'assigned_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.competitions.operators.sync', $this->competition), [
            'operator_ids' => [],
        ])->assertRedirect();

    $this->competition->load('operators');

    expect($this->competition->operators)->toHaveCount(0);
});

it('does not assign inactive operators', function () {
    $inactive = User::factory()->operator()->inactive()->create();

    $this->actingAs($this->admin)
        ->put(route('admin.competitions.operators.sync', $this->competition), [
            'operator_ids' => [$inactive->id],
        ])->assertRedirect();

    $this->competition->load('operators');

    expect($this->competition->operators)->toHaveCount(0);
});

it('operator cannot access assignment page', function () {
    $this->actingAs($this->operator)
        ->get(route('admin.competitions.operators', $this->competition))
        ->assertForbidden();
});

it('assignment works on locked competition', function () {
    $locked = Competition::factory()->locked()->create();

    $this->actingAs($this->admin)
        ->put(route('admin.competitions.operators.sync', $locked), [
            'operator_ids' => [$this->operator->id],
        ])->assertRedirect();

    $locked->load('operators');

    expect($locked->operators)->toHaveCount(1);
});

it('assigned operator can view competition internally', function () {
    $this->competition->operators()->attach($this->operator->id, [
        'assigned_by' => $this->admin->id,
        'assigned_at' => now(),
    ]);

    expect($this->operator->can('view', $this->competition))->toBeTrue();
});

it('unassigned operator cannot view competition', function () {
    $unassigned = User::factory()->operator()->create();

    expect($unassigned->can('view', $this->competition))->toBeFalse();
});
