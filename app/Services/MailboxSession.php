<?php

namespace App\Services;

use App\Models\Address;
use App\Support\Clock;
use Illuminate\Http\Request;

/**
 * Reads the student session: `mailbox.address_id` plus `mailbox.fingerprint` (Address::pinFingerprint()).
 * A session is live only while the PIN is valid and still the one the student signed in with.
 */
class MailboxSession
{
    public static function address(Request $request): ?Address
    {
        $id = $request->session()->get('mailbox.address_id');
        $address = $id ? Address::find($id) : null;

        if ($address === null
            || ! $address->pinIsValid(Clock::now())
            || ! hash_equals($address->pinFingerprint(), (string) $request->session()->get('mailbox.fingerprint'))) {
            return null;
        }

        return $address;
    }

    public static function forget(Request $request): void
    {
        $request->session()->forget(['mailbox.address_id', 'mailbox.fingerprint']);
    }
}
