<?php

namespace App\Core\Pin;

/** Generates 8-character PINs without look-alike characters (no 0/O, 1/I/L). */
final class PinGenerator
{
    public const ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    public const LENGTH = 8;

    /** @param (\Closure(int,int):int)|null $randomInt injectable replacement for random_int(min, max) */
    public function __construct(private readonly ?\Closure $randomInt = null) {}

    /** @return string PIN of LENGTH characters, all from ALPHABET, no separator */
    public function generate(): string
    {
        $randomInt = $this->randomInt ?? random_int(...);
        $maxIndex = strlen(self::ALPHABET) - 1;

        $pin = '';
        for ($i = 0; $i < self::LENGTH; $i++) {
            $pin .= self::ALPHABET[$randomInt(0, $maxIndex)];
        }

        return $pin;
    }
}
