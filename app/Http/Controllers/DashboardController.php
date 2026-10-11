<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class DashboardController extends Controller
{
    /** Breeze redirects to "dashboard" after login; teachers land on their groups. */
    public function __invoke(): RedirectResponse
    {
        return redirect()->route('groups.index');
    }
}
