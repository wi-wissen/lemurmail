<?php

namespace App\Http\Middleware;

use App\Models\Address;
use App\Support\Clock;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Student session guard. The session holds `mailbox.address_id` and `mailbox.fingerprint`
 * (Address::pinFingerprint()). It ends as soon as the PIN expired or was replaced.
 * On success the Address is available as $request->attributes->get('mailbox_address').
 */
class EnsureMailboxSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->session()->get('mailbox.address_id');
        $fingerprint = $request->session()->get('mailbox.fingerprint');
        $address = $id ? Address::find($id) : null;

        if (! $address || ! $address->pinIsValid(Clock::now()) || ! hash_equals($address->pinFingerprint(), (string) $fingerprint)) {
            $request->session()->forget(['mailbox.address_id', 'mailbox.fingerprint']);

            return $request->expectsJson()
                ? response()->json(['message' => 'session_ended'], 401)
                : redirect()->route('home')->with('warning', __('mailbox.session_ended'));
        }

        $request->attributes->set('mailbox_address', $address);

        return $next($request);
    }
}
