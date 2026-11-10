<?php

namespace App\Http\Controllers\Mailbox;

use App\Http\Controllers\Controller;
use App\Services\MailboxSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Start page: sends students to the mailbox login and teachers to their account. A student who is still
 * signed in goes straight to the inbox.
 */
class HomeController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        if (MailboxSession::address($request) !== null) {
            return redirect()->route('mailbox.inbox');
        }

        return view('home');
    }
}
