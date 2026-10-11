<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    /** Status page for teachers who cannot use the app yet (pending, rejected or blocked). */
    public function pending(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->isActive() || $user->isSuperAdmin()) {
            return redirect()->route('groups.index');
        }

        return view('account.pending', ['status' => $user->status]);
    }
}
