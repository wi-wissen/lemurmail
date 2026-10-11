<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teacher\Concerns\OwnsResources;
use App\Http\Requests\Teacher\AddressSenderStoreRequest;
use App\Http\Requests\Teacher\SenderStoreRequest;
use App\Models\Address;
use App\Models\SenderRule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SenderController extends Controller
{
    use OwnsResources;

    public function index(Request $request): View
    {
        return view('teacher.senders.index', [
            'rules' => $request->user()->senderRules()->whereNull('address_id')->orderBy('pattern')->get(),
        ]);
    }

    public function store(SenderStoreRequest $request): RedirectResponse
    {
        $request->user()->senderRules()->firstOrCreate([
            'address_id' => null,
            'pattern' => $request->normalizedPattern(),
        ]);

        return redirect()->back(fallback: route('senders.index'))->with('status', __('teacher.flash.sender_added'));
    }

    public function storeForAddress(AddressSenderStoreRequest $request, Address $address): RedirectResponse
    {
        $this->owned($request, $address);

        $request->user()->senderRules()->firstOrCreate([
            'address_id' => $address->id,
            'pattern' => $request->normalizedPattern(),
        ]);

        return redirect()->back(fallback: route('groups.index'))->with('status', __('teacher.flash.sender_added'));
    }

    public function destroy(Request $request, SenderRule $rule): RedirectResponse
    {
        $this->owned($request, $rule)->delete();

        return redirect()->back(fallback: route('senders.index'))->with('status', __('teacher.flash.sender_removed'));
    }
}
