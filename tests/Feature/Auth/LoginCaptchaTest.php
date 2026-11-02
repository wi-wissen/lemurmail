<?php

use App\Models\User;

beforeEach(function () {
    enableCaptcha();
});

it('shows the captcha on the teacher login page', function () {
    $this->get('/login')->assertSee('<altcha-widget', false)->assertSee(route('captcha.challenge'), false);
});

it('refuses a teacher login without a captcha solution', function () {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('altcha');
    $this->assertGuest();
});

it('refuses a login with a wrong captcha solution', function () {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password', 'altcha' => 'bad'])->assertSessionHasErrors('altcha');
    $this->assertGuest();
});

it('logs a teacher in with a valid captcha solution', function () {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password', 'altcha' => solvedCaptcha()])->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
});

it('accepts a solved captcha only once', function () {
    $user = User::factory()->create();
    $payload = solvedCaptcha();

    $this->post('/login', ['email' => $user->email, 'password' => 'password', 'altcha' => $payload])->assertRedirect(route('dashboard'));
    auth()->logout();
    $this->post('/login', ['email' => $user->email, 'password' => 'password', 'altcha' => $payload])->assertSessionHasErrors('altcha');
});

it('refuses a tampered solution', function () {
    $user = User::factory()->create();
    $payload = json_decode(base64_decode(solvedCaptcha()), true);
    $payload['solution']['derivedKey'] = str_repeat('0', strlen($payload['solution']['derivedKey']));

    $this->post('/login', ['email' => $user->email, 'password' => 'password', 'altcha' => base64_encode(json_encode($payload))])
        ->assertSessionHasErrors('altcha');
});

it('hands out a fresh challenge as JSON', function () {
    $this->getJson(route('captcha.challenge'))
        ->assertOk()
        ->assertJsonStructure(['parameters' => ['algorithm', 'cost', 'nonce', 'keyPrefix', 'expiresAt'], 'signature']);
});

it('does not ask for a captcha when it is switched off', function () {
    config(['altcha.enabled' => false]);
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('dashboard'));
});
