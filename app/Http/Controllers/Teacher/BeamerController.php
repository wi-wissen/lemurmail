<?php

namespace App\Http\Controllers\Teacher;

use App\Core\Pin\PinFormat;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Teacher\Concerns\OwnsResources;
use App\Models\Address;
use App\Models\Group;
use App\Support\Clock;
use App\Support\TimeLabel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BeamerController extends Controller
{
    use OwnsResources;

    public function show(Request $request, Group $group): View|RedirectResponse
    {
        $this->owned($request, $group);
        $now = Clock::now();
        $window = $group->window();

        if (! $window?->isActive($now)) {
            return redirect()->route('groups.show', $group)->with('warning', __('lesson.beamer_no_lesson'));
        }

        $pins = $group->addresses()->orderBy('local_part')->get()
            ->filter(fn (Address $address) => $address->pinIsValid($now) && $address->pin_encrypted !== null)
            ->map(fn (Address $address) => [
                'local_part' => $address->local_part,
                'pin' => PinFormat::display($address->pin_encrypted),
            ])
            ->values();

        return view('teacher.beamer', [
            'group' => $group,
            'pins' => $pins,
            'domain' => config('lemurmail.mail_domain'),
            'host' => $request->getHost(),
            'validUntil' => TimeLabel::until($window->endsAt, $now),
            'endsAt' => $window->endsAt,
            'now' => $now,
        ]);
    }
}
