<?php

namespace App\Services;

use App\Core\Pin\PinGenerator;
use App\Models\Address;
use Illuminate\Support\Facades\Hash;

/** Issues and revokes PINs. $expiresAt is a Unix timestamp in whole seconds. */
class PinIssuer
{
    public function __construct(private readonly PinGenerator $generator) {}

    /** Stores a fresh PIN (hash for checking, encrypted plaintext for the beamer) and returns the plaintext. */
    public function issue(Address $address, int $expiresAt): string
    {
        $pin = $this->generator->generate();

        $address->forceFill([
            'pin_hash' => Hash::make($pin),
            'pin_encrypted' => $pin,
            'pin_expires_at' => $expiresAt,
        ])->save();

        return $pin;
    }

    /** Moves the expiry of an existing PIN without changing it. */
    public function setExpiry(Address $address, int $expiresAt): void
    {
        $address->forceFill(['pin_expires_at' => $expiresAt])->save();
    }

    /** Makes the PIN invalid right now and drops the plaintext. $now ends the validity (pin is valid while expires > now). */
    public function revoke(Address $address, int $now): void
    {
        $address->forceFill(['pin_expires_at' => $now, 'pin_encrypted' => null])->save();
    }
}
