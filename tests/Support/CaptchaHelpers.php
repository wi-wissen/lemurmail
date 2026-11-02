<?php

use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\Challenge;
use AltchaOrg\Altcha\Payload;
use AltchaOrg\Altcha\SolveChallengeOptions;
use App\Services\CaptchaService;

/** Switches the captcha on with a cheap proof of work. */
function enableCaptcha(): void
{
    config(['altcha.enabled' => true, 'altcha.cost' => 10, 'altcha.counter_min' => 1, 'altcha.counter_max' => 5]);
}

/** Fetches a challenge from the service and solves it like the browser widget does; returns the posted payload. */
function solvedCaptcha(): string
{
    $challenge = Challenge::fromArray(app(CaptchaService::class)->challenge());
    $solution = (new Altcha)->solveChallenge(new SolveChallengeOptions(algorithm: new Pbkdf2, challenge: $challenge));

    return (new Payload($challenge, $solution))->toBase64();
}
