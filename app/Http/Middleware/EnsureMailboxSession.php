<?php

namespace App\Http\Middleware;

use App\Services\MailboxSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Student session guard (see App\Services\MailboxSession). It ends as soon as the PIN expired or was replaced.
 * On success the Address is available as $request->attributes->get('mailbox_address').
 */
class EnsureMailboxSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $address = MailboxSession::address($request);

        if ($address === null) {
            MailboxSession::forget($request);

            return $request->expectsJson()
                ? response()->json(['message' => 'session_ended'], 401)
                : redirect()->route('mailbox.login.form')->with('warning', __('mailbox.session_ended'));
        }

        $request->attributes->set('mailbox_address', $address);

        return $next($request);
    }
}
