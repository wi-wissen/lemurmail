<?php

namespace App\Http\Requests\Teacher;

use App\Core\Mail\SenderWhitelist;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class SenderStoreRequest extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'pattern' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (SenderWhitelist::normalizePattern((string) $value) === null) {
                        $fail(__('teacher.errors.invalid_sender'));
                    }
                },
            ],
        ];
    }

    /** The entry in canonical form; the rule has already accepted it. */
    public function normalizedPattern(): string
    {
        return (string) SenderWhitelist::normalizePattern($this->validated('pattern'));
    }
}
