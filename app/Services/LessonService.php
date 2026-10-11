<?php

namespace App\Services;

use App\Core\Hour\HourWindow;
use App\Models\Address;
use App\Models\Group;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Lesson windows and the PINs that belong to them. All times are Unix seconds passed in by the caller. */
class LessonService
{
    public function __construct(private readonly PinIssuer $pins) {}

    /** Starts (or restarts) a lesson: every address of the group gets a fresh PIN valid until the window ends. */
    public function start(Group $group, HourWindow $window): void
    {
        DB::transaction(function () use ($group, $window) {
            $group->update(['lesson_started_at' => $window->startedAt, 'lesson_ends_at' => $window->endsAt]);

            foreach ($group->addresses as $address) {
                $this->pins->issue($address, $window->endsAt);
            }
        });
    }

    public function extend(Group $group, int $now): void
    {
        $running = $this->runningWindow($group, $now);
        $window = $running->extend($now, config('lemurmail.extend_seconds'), $this->extendCap($running));

        DB::transaction(function () use ($group, $window, $now) {
            $group->update(['lesson_ends_at' => $window->endsAt]);

            foreach ($group->addresses as $address) {
                if ($address->pinIsValid($now)) {
                    $this->pins->setExpiry($address, $window->endsAt);
                }
            }
        });
    }

    /** Ends the lesson at once: all PINs of the group become invalid and their plaintext is dropped. */
    public function end(Group $group, int $now): void
    {
        DB::transaction(function () use ($group, $now) {
            $window = $group->window()?->end($now);

            if ($window !== null) {
                $group->update(['lesson_ends_at' => $window->endsAt]);
            }

            foreach ($group->addresses as $address) {
                $this->pins->revoke($address, $now);
            }
        });
    }

    public function regenerateGroupPins(Group $group, int $now): void
    {
        $window = $this->runningWindow($group, $now);

        DB::transaction(function () use ($group, $window) {
            foreach ($group->addresses as $address) {
                $this->pins->issue($address, $window->endsAt);
            }
        });
    }

    /** Valid until the running lesson ends, otherwise for the standalone validity. */
    public function regenerateAddressPin(Address $address, int $now): void
    {
        $window = $address->group?->window();
        $expiresAt = $window?->isActive($now)
            ? $window->endsAt
            : $now + config('lemurmail.single_pin_seconds');

        $this->pins->issue($address, $expiresAt);
    }

    /** Lessons ended by a date may run for the PIN maximum, duration based ones only for the lesson maximum. */
    private function extendCap(HourWindow $window): int
    {
        return $window->endsAt - $window->startedAt > config('lemurmail.max_lesson_seconds')
            ? config('lemurmail.max_pin_seconds')
            : config('lemurmail.max_lesson_seconds');
    }

    private function runningWindow(Group $group, int $now): HourWindow
    {
        $window = $group->window();

        if (! $window?->isActive($now)) {
            throw ValidationException::withMessages(['lesson' => __('lesson.not_running')]);
        }

        return $window;
    }
}
