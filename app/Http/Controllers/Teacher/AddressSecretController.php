<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teacher\Concerns\OwnsResources;
use App\Http\Requests\Mailbox\SecretRequest;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AddressSecretController extends Controller
{
    use OwnsResources;

    public function edit(Request $request, Address $address): View
    {
        $this->owned($request, $address);

        return view('teacher.secret', ['address' => $address]);
    }

    public function update(SecretRequest $request, Address $address): RedirectResponse
    {
        $secret = $request->validated('secret');

        $this->owned($request, $address)->update([
            'secret' => filled($secret) ? $secret : null,
        ]);

        return redirect()->route('addresses.inbox', $address);
    }

    /** The only place the secret leaves the server; never cached. */
    public function show(Request $request, Address $address): JsonResponse
    {
        return response()->json([
            'secret' => $this->owned($request, $address)->secret,
        ])->withHeaders(['Cache-Control' => 'no-store']);
    }
}
