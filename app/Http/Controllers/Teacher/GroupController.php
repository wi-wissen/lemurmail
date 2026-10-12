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

        return view('teacher.groups.show', [
            'group' => $group,
            'initialTab' => $request->session()->get('errors')?->has('name') ? 'settings' : 'addresses',
            'now' => Clock::now(),
            'addresses' => $group->addresses()->withCount('messages')->with('senderRules')->orderBy('local_part')->get(),
            'defaultRules' => $request->user()->senderRules()->whereNull('address_id')->orderBy('pattern')->get(),
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
}
