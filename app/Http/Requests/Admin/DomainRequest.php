<?php

namespace App\Http\Requests\Admin;

use App\Models\BlockedDomain;
use Illuminate\Foundation\Http\FormRequest;

class DomainRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'domain' => BlockedDomain::normalize((string) $this->input('domain')),
        ]);
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'domain' => ['required', 'string', 'max:253', 'regex:/^[a-z0-9-]+(\.[a-z0-9-]+)+$/'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'domain.required' => __('admin.errors.domain_required'),
            'domain.regex' => __('admin.errors.domain_invalid'),
            'domain.max' => __('admin.errors.domain_invalid'),
        ];
    }
}
