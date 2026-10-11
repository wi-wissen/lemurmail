<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    use VerifiesCaptcha;

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            ...$this->captchaRules(),
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return $this->captchaMessages();
    }
}
