<?php

namespace App\Http\Controllers\Mailbox;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Support\Clock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        if ($this->hasLiveSession($request)) {
            return redirect()->route('mailbox.inbox');
        }

        return view('mailbox.login');
    }

    private function hasLiveSession(Request $request): bool
    {
        $id = $request->session()->get('mailbox.address_id');
        $address = $id ? Address::find($id) : null;

        return $address !== null
            && $address->pinIsValid(Clock::now())
            && hash_equals($address->pinFingerprint(), (string) $request->session()->get('mailbox.fingerprint'));
    }
}
