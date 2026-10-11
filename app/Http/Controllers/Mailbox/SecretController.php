<?php

namespace App\Http\Controllers\Mailbox;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mailbox\SecretRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SecretController extends Controller
{
    public function edit(Request $request): View
    {
        return view('mailbox.secret', [
            'address' => $request->attributes->get('mailbox_address'),
        ]);
    }

    public function update(SecretRequest $request): RedirectResponse
    {
        $secret = $request->validated('secret');

        $request->attributes->get('mailbox_address')->update([
            'secret' => filled($secret) ? $secret : null,
        ]);

        return redirect()->route('mailbox.inbox');
    }

    /** The only place the secret leaves the server; never cached. */
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'secret' => $request->attributes->get('mailbox_address')->secret,
        ])->withHeaders(['Cache-Control' => 'no-store']);
    }
}
