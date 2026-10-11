<?php

namespace App\Core\Mail;

/** One of spf/dkim/dmarc from the webhook's authentication_results. */
final class AuthResult
{
    public function __construct(
        public readonly string $result, // pass|fail|softfail|neutral|temperror|permerror|none|unknown
        public readonly ?string $domain,
    ) {}

    public function passed(): bool
    {
        return strtolower($this->result) === 'pass';
    }
}
