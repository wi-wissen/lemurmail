<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Teachers must be approved; pending/rejected/blocked accounts are sent to the status page. */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isActive() && ! $user->isSuperAdmin()) {
            return redirect()->route('account.pending');
        }

        return $next($request);
    }
}
