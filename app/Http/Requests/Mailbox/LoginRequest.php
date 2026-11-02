<?php

namespace App\Http\Requests\Mailbox;

use App\Rules\Captcha;
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
        $captchaEnabled = (bool) config('altcha.enabled');

        return [
            'address' => ['nullable', 'string', 'max:255'],
            'pin' => ['nullable', 'string', 'max:64'],
            'altcha' => [$captchaEnabled ? 'required' : 'nullable', new Captcha],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'altcha.required' => __('captcha.required'),
        ];
    }
}
