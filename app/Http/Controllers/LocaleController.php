<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    /** Stores the chosen UI language in the session (read by SetLocale). */
    public function __invoke(string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, config('lemurmail.locales'), true), 404);
        session(['locale' => $locale]);

        return redirect()->back(fallback: '/');
    }
}
