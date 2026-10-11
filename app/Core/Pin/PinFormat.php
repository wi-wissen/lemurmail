<?php

namespace App\Core\Pin;

final class PinFormat
{
    /** "K7M4QX9R" => "K7M4 QX9R" (groups of four separated by one space). */
    public static function display(string $pin): string
    {
        return implode(' ', str_split($pin, 4));
    }

    /** User input => canonical PIN: upper-case, every character that is not A-Z/0-9 removed. */
    public static function normalize(string $input): string
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper($input));
    }

    /** True if $pin (already normalized) has LENGTH characters, all from PinGenerator::ALPHABET. */
    public static function isWellFormed(string $pin): bool
    {
        return strlen($pin) === PinGenerator::LENGTH
            && strspn($pin, PinGenerator::ALPHABET) === PinGenerator::LENGTH;
    }
}
