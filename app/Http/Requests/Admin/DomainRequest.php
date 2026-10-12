<?php

namespace App\Http\Requests\Admin;

use App\Core\Registration\DomainPolicy;
use App\Models\OrganizationDomain;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class DomainRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $pattern = trim((string) $this->input('pattern'));

        $this->merge([
            'domain' => OrganizationDomain::normalize((string) $this->input('domain')),
            'pattern' => $pattern === '' ? null : $pattern,
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'domain' => ['required', 'string', 'max:253', 'regex:/^[a-z0-9-]+(\.[a-z0-9-]+)+$/'],
            'pattern' => ['nullable', 'string', function (string $attribute, mixed $value, Closure $fail): void {
                if ($value !== null && ! DomainPolicy::isValidPattern($value)) {
                    $fail(__('admin.errors.pattern_invalid'));
                }
            }],
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
