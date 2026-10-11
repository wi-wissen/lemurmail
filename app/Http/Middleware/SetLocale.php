<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/** Chooses the UI locale: explicit session choice, then Accept-Language, then the fallback. */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = config('lemurmail.locales');
        $locale = $request->hasSession() ? $request->session()->get('locale') : null;

        if (! in_array($locale, $supported, true)) {
            $locale = $request->getPreferredLanguage($supported) ?? config('lemurmail.fallback_locale');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
