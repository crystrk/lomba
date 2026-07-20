<?php

use Laravel\Fortify\Features;

test('registration screen is not available', function () {
    if (Features::enabled(Features::registration())) {
        $this->markTestSkipped('Registration feature is enabled.');
    }

    $response = $this->get('/register');

    $response->assertNotFound();
});

test('register route is disabled', function () {
    if (Features::enabled(Features::registration())) {
        $this->markTestSkipped('Registration feature is enabled.');
    }

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertNotFound();
});
