<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Verifies a Friendly Captcha (v1) solution against the siteverify endpoint.
 * Fails open only when the verification service itself is unreachable (vendor guidance);
 * the PIN/registration rate limits still apply in that case.
 */
class FriendlyCaptcha implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! config('friendlycaptcha.enabled')) {
            return;
        }

        if (! is_string($value) || $value === '') {
            $fail('captcha.required')->translate();

            return;
        }

        try {
            $response = Http::asForm()->timeout(5)->post(config('friendlycaptcha.verify_endpoint'), [
                'solution' => $value,
                'secret' => config('friendlycaptcha.secret'),
                'sitekey' => config('friendlycaptcha.sitekey'),
            ]);
        } catch (Throwable $e) {
            Log::warning('Friendly Captcha verification unreachable');

            return;
        }

        if ($response->serverError()) {
            Log::warning('Friendly Captcha verification service error');

            return;
        }

        if ($response->json('success') !== true) {
            $fail('captcha.failed')->translate();
        }
    }
}
