<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['friendlycaptcha.enabled' => true, 'friendlycaptcha.secret' => 's', 'friendlycaptcha.sitekey' => 'k']);
});

it('shows the captcha on the teacher login page', function () {
    $this->get('/login')->assertSee('frc-captcha', false)->assertSee('data-sitekey="k"', false);
});

it('refuses a teacher login without a captcha solution', function () {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('frc-captcha-solution');
    $this->assertGuest();
});

it('refuses a login with a failed captcha', function () {
    Http::fake(['*' => Http::response(['success' => false])]);
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password', 'frc-captcha-solution' => 'bad'])->assertSessionHasErrors('frc-captcha-solution');
    $this->assertGuest();
});

it('logs a teacher in with a valid captcha solution', function () {
    Http::fake(['*' => Http::response(['success' => true])]);
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password', 'frc-captcha-solution' => 'good'])->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
});

it('does not ask for a captcha when it is switched off', function () {
    config(['friendlycaptcha.enabled' => false]);
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('dashboard'));
});
