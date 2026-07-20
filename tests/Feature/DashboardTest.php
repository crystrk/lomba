<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('admin.dashboard'));
    $response->assertRedirect(route('login'));
});

test('admin can visit admin dashboard', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    $response = $this->get(route('admin.dashboard'));
    $response->assertOk();
});

test('operator cannot visit admin dashboard', function () {
    $user = User::factory()->operator()->create();
    $this->actingAs($user);

    $response = $this->get(route('admin.dashboard'));
    $response->assertForbidden();
});

test('operator can visit operator dashboard', function () {
    $user = User::factory()->operator()->create();
    $this->actingAs($user);

    $response = $this->get(route('operator.dashboard'));
    $response->assertOk();
});

test('admin cannot visit operator dashboard', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    $response = $this->get(route('operator.dashboard'));
    $response->assertForbidden();
});

test('inactive user is logged out on subsequent request', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    $user->update(['is_active' => false]);

    $response = $this->get(route('admin.dashboard'));
    $response->assertRedirect(route('login'));
    $this->assertGuest();
});
