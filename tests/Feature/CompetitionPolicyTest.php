<?php

use App\Models\Competition;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->operator = User::factory()->operator()->create();
    $this->unassignedOperator = User::factory()->operator()->create();
});

it('admin can view any competition', function () {
    $competition = Competition::factory()->create();

    expect($this->admin->can('view', $competition))->toBeTrue();
});

it('operator can view assigned competition', function () {
    $competition = Competition::factory()->create();
    $competition->operators()->attach($this->operator->id, [
        'assigned_by' => $this->admin->id,
        'assigned_at' => now(),
    ]);

    expect($this->operator->can('view', $competition))->toBeTrue();
});

it('operator cannot view unassigned competition', function () {
    $competition = Competition::factory()->create();

    expect($this->unassignedOperator->can('view', $competition))->toBeFalse();
});

it('admin can create competition', function () {
    expect($this->admin->can('create', Competition::class))->toBeTrue();
});

it('operator cannot create competition', function () {
    expect($this->operator->can('create', Competition::class))->toBeFalse();
});

it('admin can update draft competition', function () {
    $competition = Competition::factory()->draft()->create();

    expect($this->admin->can('update', $competition))->toBeTrue();
});

it('admin can update locked competition', function () {
    $competition = Competition::factory()->locked()->create();

    expect($this->admin->can('update', $competition))->toBeTrue();
});

it('admin can delete draft competition', function () {
    $competition = Competition::factory()->draft()->create();

    expect($this->admin->can('delete', $competition))->toBeTrue();
});

it('admin cannot delete locked competition', function () {
    $competition = Competition::factory()->locked()->create();

    expect($this->admin->can('delete', $competition))->toBeFalse();
});

it('admin can draw competition', function () {
    $competition = Competition::factory()->draft()->create();

    expect($this->admin->can('draw', $competition))->toBeTrue();
});

it('admin can lock drawn competition', function () {
    $competition = Competition::factory()->drawn()->create();

    expect($this->admin->can('lock', $competition))->toBeTrue();
});

it('admin cannot lock draft competition', function () {
    $competition = Competition::factory()->draft()->create();

    expect($this->admin->can('lock', $competition))->toBeFalse();
});

it('admin can update score on any competition', function () {
    $competition = Competition::factory()->locked()->create();

    expect($this->admin->can('updateScore', $competition))->toBeTrue();
});

it('operator can update score on assigned competition', function () {
    $competition = Competition::factory()->locked()->create();
    $competition->operators()->attach($this->operator->id, [
        'assigned_by' => $this->admin->id,
        'assigned_at' => now(),
    ]);

    expect($this->operator->can('updateScore', $competition))->toBeTrue();
});

it('operator cannot update score on unassigned competition', function () {
    $competition = Competition::factory()->locked()->create();

    expect($this->unassignedOperator->can('updateScore', $competition))->toBeFalse();
});

it('operator cannot update score on draft competition', function () {
    $competition = Competition::factory()->draft()->create();
    $competition->operators()->attach($this->operator->id, [
        'assigned_by' => $this->admin->id,
        'assigned_at' => now(),
    ]);

    expect($this->operator->can('updateScore', $competition))->toBeFalse();
});

it('guest cannot do anything', function () {
    $competition = Competition::factory()->create();

    expect(auth()->user())->toBeNull();
});
