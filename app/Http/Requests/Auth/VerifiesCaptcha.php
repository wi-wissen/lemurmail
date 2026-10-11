<?php

namespace App\Http\Requests\Auth;

use App\Rules\FriendlyCaptcha;
use Illuminate\Validation\Rule;

/** Captcha field for forms that must not be submitted by bots (only enforced when the captcha is enabled). */
trait VerifiesCaptcha
{
    /** @return array<string, list<mixed>> */
    protected function captchaRules(): array
    {
        return [
            'frc-captcha-solution' => [Rule::requiredIf(fn () => (bool) config('friendlycaptcha.enabled')), new FriendlyCaptcha],
        ];
    }

    /** @return array<string, string> */
    protected function captchaMessages(): array
    {
        return ['frc-captcha-solution.required' => __('captcha.required')];
    }
}
