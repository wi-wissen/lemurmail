<?php

namespace App\Core\Registration;

/**
 * Classification order: Invalid, OwnMailDomain, Freemail, Approved, Unknown.
 * Domain comparison is case-insensitive. OwnMailDomain and Freemail also match subdomains
 * ("x.gmail.com"); Approved matches the exact domain only.
 */
final class DomainPolicy
{
    /**
     * @param  list<string>  $freemailDomains
     * @param  list<string>  $approvedDomains
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
        if (in_array($domain, array_map(self::normalizeDomain(...), $this->approvedDomains), true)) {
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
