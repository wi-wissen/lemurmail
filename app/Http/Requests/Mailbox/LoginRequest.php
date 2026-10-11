<?php

namespace App\Http\Requests\Mailbox;

use App\Rules\FriendlyCaptcha;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Field presence is not checked here: a missing address or PIN is a failed login like any other
 * and must produce the same message (see LoginController).
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $captchaEnabled = (bool) config('friendlycaptcha.enabled');

        return [
            'address' => ['nullable', 'string', 'max:255'],
            'pin' => ['nullable', 'string', 'max:64'],
            'frc-captcha-solution' => [$captchaEnabled ? 'required' : 'nullable', new FriendlyCaptcha],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'frc-captcha-solution.required' => __('captcha.required'),
        ];
    }
}
