<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Teacher\Concerns\OwnsResources;
use App\Http\Requests\Teacher\LessonStartRequest;
use App\Models\Group;
use App\Services\LessonService;
use App\Support\Clock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    use OwnsResources;

    public function __construct(private readonly LessonService $lessons) {}

    public function start(LessonStartRequest $request, Group $group): RedirectResponse
    {
        $this->lessons->start($group, $request->window());

        return redirect()->route('groups.show', $group)->with('status', __('lesson.started'));
    }

    public function extend(Request $request, Group $group): RedirectResponse
    {
        $this->owned($request, $group);
        $this->lessons->extend($group, Clock::now());

        return redirect()->back(fallback: route('groups.show', $group))->with('status', __('lesson.extended'));
    }

    public function end(Request $request, Group $group): RedirectResponse
    {
        $this->owned($request, $group);
        $this->lessons->end($group, Clock::now());

        return redirect()->route('groups.show', $group)->with('status', __('lesson.ended'));
    }
}
