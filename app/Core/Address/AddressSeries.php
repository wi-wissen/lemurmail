<?php

namespace App\Core\Address;

final class AddressSeries
{
    /**
     * "klasse7a-" + 30 => klasse7a-01 ... klasse7a-30. The number is zero-padded to
     * max(2, digits of ($start + $count - 1)). The prefix is normalized first.
     *
     * @return list<string> local parts
     *
     * @throws \InvalidArgumentException if $count < 1, $start < 0, or any resulting local part is not valid
     */
    public static function generate(string $prefix, int $count, int $start = 1): array
    {
        $prefix = LocalPart::normalize($prefix);
        if ($count < 1 || $start < 0 || $prefix === '') {
            throw new \InvalidArgumentException('Invalid address series.');
        }

        $last = $start + $count - 1;
        $width = max(2, strlen((string) $last));
        $localParts = [];
        for ($number = $start; $number <= $last; $number++) {
            $localPart = $prefix.str_pad((string) $number, $width, '0', STR_PAD_LEFT);
            if (! LocalPart::isValid($localPart)) {
                throw new \InvalidArgumentException('Invalid address series.');
            }
            $localParts[] = $localPart;
        }

        return $localParts;
    }
}
