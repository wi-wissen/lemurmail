<?php

use App\Models\OrganizationDomain;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\App;

it('renders the e-mail verification mail in the language of the page', function () {
    $user = User::factory()->unverified()->create();
    App::setLocale('de');

    $html = (string) (new VerifyEmail)->toMail($user)->render();

    expect($html)->toContain('E-Mail-Adresse bestätigen')
        ->toContain('Hallo!')
        ->toContain('Viele Grüße')
        ->not->toContain('Verify Email Address')
        ->not->toContain('Regards');
});

it('renders the password reset mail in German and keeps English for English', function () {
    $user = User::factory()->create();
    App::setLocale('de');
    expect((string) (new ResetPassword('token'))->toMail($user)->render())->toContain('Passwort zurücksetzen')->not->toContain('Reset Password');

    App::setLocale('en');
    expect((string) (new ResetPassword('token'))->toMail($user)->render())->toContain('Reset Password');
});

it('sends the verification mail on registration in the language of the browser', function () {
    OrganizationDomain::create(['domain' => 'schule.example.org']);
    config(['altcha.enabled' => false]);

    $this->withHeaders(['Accept-Language' => 'de-DE,de;q=0.9'])
        ->post('/register', ['email' => 'lehrer@schule.example.org', 'password' => 'a-long-password-1']);

    $messages = app('mailer')->getSymfonyTransport()->messages();
    expect($messages)->toHaveCount(1);
    $body = $messages[0]->getOriginalMessage()->getHtmlBody();
    expect($body)->toContain('E-Mail-Adresse bestätigen')->not->toContain('Verify Email Address');
});
