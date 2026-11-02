<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teacher\Concerns\OwnsResources;
use App\Http\Requests\Teacher\GroupRequest;
use App\Models\Group;
use App\Support\Clock;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupController extends Controller
{
    use OwnsResources;

    public function index(Request $request): View
    {
        return view('teacher.groups.index', [
            'groups' => $request->user()->groups()->withCount('addresses')->orderBy('name')->get(),
        ]);
    }

    public function store(GroupRequest $request): RedirectResponse
    {
        $group = $request->user()->groups()->create($request->validated());

        return redirect()->route('groups.show', $group)->with('status', __('teacher.flash.group_created'));
    }

    public function show(Request $request, Group $group): View
    {
        $this->owned($request, $group);

        $addresses = $group->addresses()->withCount('messages')->with('senderRules')->orderBy('local_part')->get();
        $defaultRules = $request->user()->senderRules()->whereNull('address_id')->orderBy('pattern')->get();

        return view('teacher.groups.show', [
            'group' => $group,
            'initialTab' => $this->initialTab($request),
            'now' => Clock::now(),
            'addresses' => $addresses,
            'defaultRules' => $defaultRules,
            // Addresses that accept no mail at all: no default sender and none of their own.
            'silentCount' => $defaultRules->isEmpty() ? $addresses->filter(fn ($a) => $a->senderRules->isEmpty())->count() : 0,
        ]);
    }

    public function update(GroupRequest $request, Group $group): RedirectResponse
    {
        $this->owned($request, $group)->update($request->validated());

        return redirect()->route('groups.show', $group)->with('status', __('teacher.flash.group_renamed'));
    }

    public function destroy(Request $request, Group $group): RedirectResponse
    {
        $this->owned($request, $group);

        // Deleting a group deletes its addresses; their mails and per-address sender rules follow by foreign key.
        DB::transaction(function () use ($group) {
            $group->addresses()->delete();
            $group->delete();
        });

        return redirect()->route('groups.index')->with('status', __('teacher.flash.group_deleted'));
    }

    /** Tab of the group page that is open on load: the one a validation error or a sender change came from. */
    private function initialTab(Request $request): string
    {
        $errors = $request->session()->get('errors');

        return match (true) {
            (bool) $errors?->has('name') => 'settings',
            (bool) $errors?->has('pattern') => 'senders',
            default => $request->session()->get('tab') === 'senders' ? 'senders' : 'addresses',
        };
    }
}
