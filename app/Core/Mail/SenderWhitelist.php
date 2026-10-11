<?php

namespace App\Core\Mail;

/**
 * Entries: an exact address ("noreply@x.org"), "@x.org" or "x.org" (the whole domain x.org,
 * NOT its subdomains). Matching is case-insensitive. An empty whitelist matches nothing.
 */
final class SenderWhitelist
{
    /** @param list<string> $patterns */
    public function __construct(private readonly array $patterns) {}

    public function isEmpty(): bool
    {
        return $this->normalizedPatterns() === [];
    }

    public function matches(string $email): bool
    {
        $email = strtolower(trim($email));
        if (substr_count($email, '@') !== 1) {
            return false;
        }

        [$local, $domain] = explode('@', $email);
        if ($local === '') {
            return false;
        }

        $patterns = $this->normalizedPatterns();

        return in_array($email, $patterns, true) || in_array('@'.$domain, $patterns, true);
    }

    /** Canonical form of an entry ("@X.org " => "@x.org", "X.org" => "@x.org", "A@X.org" => "a@x.org"); null if not a valid entry. */
    public static function normalizePattern(string $pattern): ?string
    {
        $pattern = strtolower(trim($pattern));
        if (str_starts_with($pattern, '@')) {
            return self::isDomain(substr($pattern, 1)) ? $pattern : null;
        }
        if (! str_contains($pattern, '@')) {
            return self::isDomain($pattern) ? '@'.$pattern : null;
        }

        [$local, $domain] = explode('@', $pattern, 2);

        return preg_match('/^[^\s@]+$/D', $local) === 1 && self::isDomain($domain) ? $pattern : null;
    }

    /** @return list<string> */
    private function normalizedPatterns(): array
    {
        return array_values(array_filter(
            array_map(self::normalizePattern(...), $this->patterns),
            fn (?string $pattern): bool => $pattern !== null,
        ));
    }

    private static function isDomain(string $domain): bool
    {
        return preg_match('/^[a-z0-9-]+(\.[a-z0-9-]+)+$/D', $domain) === 1;
    }
}
