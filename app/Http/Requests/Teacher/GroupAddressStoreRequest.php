<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class GroupAddressStoreRequest extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'prefix' => ['required', 'string', 'max:60'],
            'count' => ['required', 'integer', 'between:1,1000'],
        ];
    }
}
