<?php

namespace App\Services;

use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\CreateChallengeOptions;
use AltchaOrg\Altcha\VerifySolutionOptions;
use App\Support\Clock;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * ALTCHA proof-of-work captcha: hands out signed challenges and checks the solutions the widget posts.
 * Nothing is stored for a challenge; only a solved one is remembered until it expires, so it works once.
 */
class CaptchaService
{
    private function altcha(): Altcha
    {
        $secret = (string) (config('altcha.secret') ?: config('app.key'));

        return new Altcha(
            hmacSignatureSecret: hash_hmac('sha256', 'altcha-signature', $secret),
            hmacKeySignatureSecret: hash_hmac('sha256', 'altcha-key', $secret),
        );
    }

    /** @return array<string, mixed> */
    public function challenge(): array
    {
        return $this->altcha()->createChallenge(new CreateChallengeOptions(
            algorithm: new Pbkdf2,
            cost: (int) config('altcha.cost'),
            counter: random_int((int) config('altcha.counter_min'), (int) config('altcha.counter_max')),
            expiresAt: Clock::now() + (int) config('altcha.ttl'),
        ))->toArray();
    }

    public function verify(string $payload): bool
    {
        try {
            $result = $this->altcha()->verifySolution(new VerifySolutionOptions(
                algorithm: new Pbkdf2,
                payload: $payload,
            ));
        } catch (Throwable) {
            return false;
        }

        if (! $result->verified) {
            return false;
        }

        // Single use: the signature identifies the challenge; remember it until the challenge has expired anyway.
        return Cache::add('altcha:'.hash('sha256', $payload), 1, (int) config('altcha.ttl') + 60);
    }
}
