<?php

namespace App\Http\Requests\Auth;

use App\Core\Registration\DomainClass;
use App\Core\Registration\DomainPolicy;
use App\Models\BlockedDomain;
use App\Models\OrganizationDomain;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    use VerifiesCaptcha;

    private ?DomainClass $domainClass = null;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
        ]);
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'email' => [
                'required', 'string', 'email:rfc', 'max:255', 'unique:'.User::class,
                function (string $attribute, mixed $value, Closure $fail): void {
                    match ($this->domainClass()) {
                        DomainClass::OwnMailDomain => $fail(__('auth.own_domain_not_allowed')),
                        DomainClass::Freemail => $fail(__('auth.freemail_not_allowed')),
                        default => null,
                    };
                },
            ],
            'password' => ['required', 'string', Password::defaults()],
            ...$this->captchaRules(),
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return $this->captchaMessages();
    }

    /** Domain class of the submitted address; Approved teachers are activated at once, Unknown ones wait for an admin. */
    public function domainClass(): DomainClass
    {
        return $this->domainClass ??= (new DomainPolicy(
            ownMailDomain: config('lemurmail.mail_domain'),
            freemailDomains: BlockedDomain::pluck('domain')->all(),
            approvedDomains: OrganizationDomain::pluck('domain')->all(),
        ))->classify($this->string('email')->value());
    }
}
