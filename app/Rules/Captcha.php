<?php

namespace App\Rules;

use App\Services\CaptchaService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Checks the ALTCHA solution posted by the widget (field "altcha"). */
class Captcha implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! config('altcha.enabled')) {
            return;
        }

        if (! is_string($value) || $value === '') {
            $fail('captcha.required')->translate();

            return;
        }

        if (! app(CaptchaService::class)->verify($value)) {
            $fail('captcha.failed')->translate();
        }
    }
}
