<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddressStoreRequest extends FormRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'local_part' => ['required', 'string', 'max:64'],
            'group_id' => ['nullable', Rule::exists('groups', 'id')->where('user_id', $this->user()->id)],
        ];
    }
}
