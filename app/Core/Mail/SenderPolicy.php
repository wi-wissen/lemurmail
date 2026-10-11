<?php

namespace App\Core\Mail;

/**
 * Accept iff (1) the From address is on the whitelist AND (2) the sender is authenticated for
 * the From domain: DMARC passed, OR DKIM passed with a signing domain aligned to the From domain,
 * OR SPF passed with a domain aligned to the From domain. "Aligned" = equal to the From domain or
 * one of its subdomains or its parent organisational domain (compare label-wise, case-insensitive;
 * "mail.example.org" is aligned with "example.org" and vice versa; "evil-example.org" is not).
 * Missing auth data (null) never authenticates.
 * The whitelist is checked first: not listed => NotAllowed even if unauthenticated.
 */
final class SenderPolicy
{
    public static function decide(
        SenderWhitelist $whitelist,
        string $fromEmail,
        ?AuthResult $spf,
        ?AuthResult $dkim,
        ?AuthResult $dmarc,
    ): SenderDecision {
        if (! $whitelist->matches($fromEmail)) {
            return SenderDecision::NotAllowed;
        }

        [, $fromDomain] = explode('@', strtolower(trim($fromEmail)), 2);
        if ($dmarc !== null && $dmarc->passed()) {
            return SenderDecision::Accept;
        }
        if (self::alignedPass($dkim, $fromDomain) || self::alignedPass($spf, $fromDomain)) {
            return SenderDecision::Accept;
        }

        return SenderDecision::Unauthenticated;
    }

    private static function alignedPass(?AuthResult $result, string $fromDomain): bool
    {
        return $result !== null
            && $result->passed()
            && $result->domain !== null
            && self::aligned(strtolower($result->domain), $fromDomain);
    }

    /** The parent must have at least two labels, so a bare TLD never aligns with a domain below it. */
    private static function aligned(string $authDomain, string $fromDomain): bool
    {
        if ($authDomain === $fromDomain || str_ends_with($authDomain, '.'.$fromDomain)) {
            return true;
        }

        return str_ends_with($fromDomain, '.'.$authDomain) && str_contains($authDomain, '.');
    }
}
