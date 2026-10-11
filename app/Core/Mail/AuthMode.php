<?php

namespace App\Core\Mail;

/** How strictly the sender of a whitelisted mail has to be authenticated. */
enum AuthMode: string
{
    /** DMARC passed, or DKIM/SPF passed for a domain aligned with the From domain (default). */
    case Aligned = 'aligned';

    /** Any of SPF, DKIM or DMARC passed, whichever domain it is for. Catches broken mail but not a determined spoofer. */
    case Authenticated = 'authenticated';

    /** No authentication check; only the whitelist (and the inbound spam filter of the mail service) protect. */
    case Off = 'off';

    /** Unknown values fall back to the strict default. */
    public static function fromConfig(mixed $value): self
    {
        return is_string($value) ? (self::tryFrom(strtolower(trim($value))) ?? self::Aligned) : self::Aligned;
    }
}
