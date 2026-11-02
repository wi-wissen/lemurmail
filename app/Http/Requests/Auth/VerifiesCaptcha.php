<?php

namespace App\Http\Requests\Auth;

use App\Rules\Captcha;
use Illuminate\Validation\Rule;

/** Captcha field for forms that must not be submitted by bots (only enforced when the captcha is enabled). */
trait VerifiesCaptcha
{
    /** @return array<string, list<mixed>> */
    protected function captchaRules(): array
    {
        return [
            'altcha' => [Rule::requiredIf(fn () => (bool) config('altcha.enabled')), new Captcha],
        ];
    }

    /** @return array<string, string> */
    protected function captchaMessages(): array
    {
        return ['altcha.required' => __('captcha.required')];
    }
}
