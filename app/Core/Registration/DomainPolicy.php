<?php

namespace App\Core\Registration;

/**
 * Classification order: Invalid, OwnMailDomain, Freemail, Approved, Unknown.
 * Domain comparison is case-insensitive. OwnMailDomain and Freemail also match subdomains
 * ("x.gmail.com"); Approved matches the exact domain only.
 *
 * An approved entry is either a domain string (the whole domain) or
 * ['domain' => string, 'pattern' => ?string], where the pattern is a regular expression that
 * must match the whole local part (the name before the @), case-insensitively.
 */
final class DomainPolicy
{
    private const MAX_PATTERN_LENGTH = 200;

    /**
     * @param  list<string>  $freemailDomains
     * @param  list<string|array{domain: string, pattern: ?string}>  $approvedDomains
     */
    public function __construct(
        private readonly string $ownMailDomain,
        private readonly array $freemailDomains,
        private readonly array $approvedDomains,
    ) {}

    public function classify(string $email): DomainClass
    {
        $domain = self::domainOf($email);
        if ($domain === null) {
            return DomainClass::Invalid;
        }
        if (self::inDomain($domain, $this->ownMailDomain)) {
            return DomainClass::OwnMailDomain;
        }
        foreach ($this->freemailDomains as $freemailDomain) {
            if (self::inDomain($domain, $freemailDomain)) {
                return DomainClass::Freemail;
            }
        }
        if ($this->isApproved($email, $domain)) {
            return DomainClass::Approved;
        }

        return DomainClass::Unknown;
    }

    /** Domain part of an e-mail address, lower-case, or null if the address is not valid. */
    public static function domainOf(string $email): ?string
    {
        $parts = explode('@', strtolower(trim($email)));
        if (count($parts) !== 2 || preg_match('/^[^\s@]+$/D', $parts[0]) !== 1 || ! self::isDomain($parts[1])) {
            return null;
        }

        return $parts[1];
    }

    /** A pattern for the name before the @: non-empty, at most 200 characters, and it must compile. */
    public static function isValidPattern(string $pattern): bool
    {
        return $pattern !== ''
            && strlen($pattern) <= self::MAX_PATTERN_LENGTH
            && self::matchesWhole($pattern, '') !== null;
    }

    private function isApproved(string $email, string $domain): bool
    {
        $localPart = strstr(strtolower(trim($email)), '@', true);

        foreach ($this->approvedDomains as $entry) {
            [$entryDomain, $pattern] = is_array($entry)
                ? [$entry['domain'], $entry['pattern'] ?? null]
                : [$entry, null];

            if (self::normalizeDomain($entryDomain) !== $domain) {
                continue;
            }
            if ($pattern === null || $pattern === '' || self::matchesWhole($pattern, $localPart) === true) {
                return true;
            }
        }

        return false;
    }

    /** True or false if the pattern runs; null if it does not compile or hits the backtrack limit. */
    private static function matchesWhole(string $pattern, string $subject): ?bool
    {
        set_error_handler(static fn (): bool => true);
        try {
            $found = preg_match(self::wrapPattern($pattern), $subject, $matches);
        } finally {
            restore_error_handler();
        }

        if ($found === false) {
            return null;
        }

        return $found === 1 && $matches[0] === $subject;
    }

    /**
     * Anchors the pattern and escapes every unescaped "~", the delimiter used here.
     * Backslash escapes are copied as pairs so an escaped character is never reinterpreted.
     */
    private static function wrapPattern(string $pattern): string
    {
        $body = '';
        $length = strlen($pattern);
        for ($i = 0; $i < $length; $i++) {
            $char = $pattern[$i];
            if ($char === '\\' && $i + 1 < $length) {
                $body .= $char.$pattern[++$i];
            } else {
                $body .= $char === '~' ? '\~' : $char;
            }
        }

        return '~^(?:'.$body.')$~iD';
    }

    private static function inDomain(string $domain, string $base): bool
    {
        $base = self::normalizeDomain($base);

        return $domain === $base || str_ends_with($domain, '.'.$base);
    }

    private static function normalizeDomain(string $domain): string
    {
        return strtolower(trim($domain));
    }

    private static function isDomain(string $domain): bool
    {
        return preg_match('/^[a-z0-9-]+(\.[a-z0-9-]+)+$/D', $domain) === 1;
    }
}
