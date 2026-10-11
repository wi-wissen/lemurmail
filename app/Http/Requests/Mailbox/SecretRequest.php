<?php

namespace App\Http\Requests\Mailbox;

use Illuminate\Foundation\Http\FormRequest;

class SecretRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'secret' => ['nullable', 'string', 'max:500'],
        ];
    }
}
