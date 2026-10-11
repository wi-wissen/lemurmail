<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teacher\Concerns\OwnsResources;
use App\Models\Address;
use App\Models\Group;
use App\Services\LessonService;
use App\Support\Clock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PinRegenerateController extends Controller
{
    use OwnsResources;

    public function __construct(private readonly LessonService $lessons) {}

    public function group(Request $request, Group $group): RedirectResponse
    {
        $this->owned($request, $group);
        $this->lessons->regenerateGroupPins($group, Clock::now());

        return redirect()->route('groups.show', $group)->with('status', __('lesson.pins_regenerated'));
    }

    public function address(Request $request, Address $address): RedirectResponse
    {
        $this->owned($request, $address);
        $this->lessons->regenerateAddressPin($address, Clock::now());

        return redirect()->back(fallback: route('groups.index'))->with('status', __('lesson.pin_regenerated'));
    }
}
