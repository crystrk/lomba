<?php

use App\Enums\UserRole;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->operator = User::factory()->operator()->create();
});

it('only admin can view operator list', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.operators.index'))
        ->assertOk();

    $this->actingAs($this->operator)
        ->get(route('admin.operators.index'))
        ->assertForbidden();
});

it('only admin can create operator', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.operators.create'))
        ->assertOk();

    $this->actingAs($this->operator)
        ->get(route('admin.operators.create'))
        ->assertForbidden();
});

it('admin can store operator', function () {
    $this->actingAs($this->admin);

    $response = $this->post(route('admin.operators.store'), [
        'name' => 'New Operator',
        'email' => 'operator@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('admin.operators.index'));

    $user = User::where('email', 'operator@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(UserRole::Operator)
        ->and($user->is_active)->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull();
});

it('admin can view edit operator page', function () {
    $operator = User::factory()->operator()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.operators.edit', $operator))
        ->assertOk();

    $this->actingAs($this->operator)
        ->get(route('admin.operators.edit', $operator))
        ->assertForbidden();
});

it('admin can update operator', function () {
    $operator = User::factory()->operator()->create();

    $this->actingAs($this->admin)
        ->put(route('admin.operators.update', $operator), [
            'name' => 'Updated Name',
            'email' => $operator->email,
        ])
        ->assertRedirect(route('admin.operators.index'));

    expect($operator->fresh()->name)->toBe('Updated Name');
});

it('admin can update operator with new password', function () {
    $operator = User::factory()->operator()->create();

    $this->actingAs($this->admin)
        ->put(route('admin.operators.update', $operator), [
            'name' => $operator->name,
            'email' => $operator->email,
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ])
        ->assertRedirect(route('admin.operators.index'));
});

it('admin can toggle operator active status', function () {
    $operator = User::factory()->operator()->create();

    expect($operator->is_active)->toBeTrue();

    $this->actingAs($this->admin)
        ->patch(route('admin.operators.toggle-active', $operator))
        ->assertRedirect(route('admin.operators.index'));

    expect($operator->fresh()->is_active)->toBeFalse();

    $this->actingAs($this->admin)
        ->patch(route('admin.operators.toggle-active', $operator))
        ->assertRedirect(route('admin.operators.index'));

    expect($operator->fresh()->is_active)->toBeTrue();
});

it('operator cannot manage other operators', function () {
    $operator = User::factory()->operator()->create();

    $this->actingAs($this->operator)
        ->post(route('admin.operators.store'), [
            'name' => 'Another',
            'email' => 'another@example.com',
            'password' => 'password',
        ])
        ->assertForbidden();

    $this->actingAs($this->operator)
        ->put(route('admin.operators.update', $operator), [
            'name' => 'Hacked',
            'email' => $operator->email,
        ])
        ->assertForbidden();
});

it('guest cannot access operator management', function () {
    $this->get(route('admin.operators.index'))->assertRedirect(route('login'));
    $this->get(route('admin.operators.create'))->assertRedirect(route('login'));
});

it('validates required fields for operator creation', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.operators.store'), [])
        ->assertSessionHasErrors(['name', 'email', 'password']);
});

it('validates unique email for operator', function () {
    $existing = User::factory()->operator()->create();

    $this->actingAs($this->admin)
        ->post(route('admin.operators.store'), [
            'name' => 'Test',
            'email' => $existing->email,
            'password' => 'password',
        ])
        ->assertSessionHasErrors(['email']);
});
