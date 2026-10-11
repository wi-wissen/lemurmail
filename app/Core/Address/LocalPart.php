<?php

namespace App\Core\Address;

final class LocalPart
{
    public const MAX_LENGTH = 64;

    /** Trim + lower-case. */
    public static function normalize(string $value): string
    {
        return strtolower(trim($value));
    }

    /**
     * Valid = 1..MAX_LENGTH chars of a-z 0-9 - _ . (already lower-case), must start and end with
     * a letter or digit and must not contain "..".
     */
    public static function isValid(string $value): bool
    {
        return strlen($value) <= self::MAX_LENGTH
            && preg_match('/^[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?$/D', $value) === 1
            && ! str_contains($value, '..');
    }
}
