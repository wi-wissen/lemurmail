<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Breeze redirects to "dashboard" after login. Teachers land on their groups, or on the allowed senders as long
     * as there is none: without one no address can be created and no mail is accepted.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $hasSenders = $request->user()->senderRules()->whereNull('address_id')->exists();

        return redirect()->route($hasSenders ? 'groups.index' : 'senders.index');
    }
}
