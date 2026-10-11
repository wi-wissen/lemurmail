<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teacher\Concerns\OwnsResources;
use App\Http\Requests\Teacher\AddressStoreRequest;
use App\Http\Requests\Teacher\GroupAddressStoreRequest;
use App\Models\Address;
use App\Models\Group;
use App\Services\AddressCreator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    use OwnsResources;

    public function __construct(private readonly AddressCreator $creator) {}

    public function storeSeries(GroupAddressStoreRequest $request, Group $group): RedirectResponse
    {
        $this->owned($request, $group);

        $addresses = $this->creator->createSeries(
            $request->user(),
            $group,
            $request->validated('prefix'),
            $request->integer('count'),
        );

        return redirect()->route('groups.show', $group)
            ->with('status', trans_choice('teacher.flash.addresses_created', count($addresses), ['count' => count($addresses)]));
    }

    public function store(AddressStoreRequest $request): RedirectResponse
    {
        // The request rule already limits group_id to the teacher's own groups.
        $group = $request->filled('group_id') ? Group::find($request->integer('group_id')) : null;

        $this->creator->createSingle($request->user(), $group, $request->validated('local_part'));

        return redirect()->back(fallback: route('groups.index'))->with('status', __('teacher.flash.address_created'));
    }

    public function destroy(Request $request, Address $address): RedirectResponse
    {
        $this->owned($request, $address)->delete();

        return redirect()->back(fallback: route('groups.index'))->with('status', __('teacher.flash.address_deleted'));
    }
}
