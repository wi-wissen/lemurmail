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

    /**
     * Starts (or restarts) a lesson: every address of the group gets a fresh PIN valid until the window ends,
     * except addresses with their own PIN (see Address::hasOwnPin()).
     */
    public function start(Group $group, HourWindow $window): void
    {
        DB::transaction(function () use ($group, $window) {
            $previousEnd = $group->lesson_ends_at;
            $group->update(['lesson_started_at' => $window->startedAt, 'lesson_ends_at' => $window->endsAt]);

            foreach ($group->addresses as $address) {
                if (! $address->hasOwnPin($previousEnd, $window->startedAt)) {
                    $this->pins->issue($address, $window->endsAt);
                }
            }
        });
    }

    public function extend(Group $group, int $now): void
    {
        $running = $this->runningWindow($group, $now);
        $window = $running->extend($now, config('lemurmail.extend_seconds'), $this->extendCap($running));

        DB::transaction(function () use ($group, $running, $window, $now) {
            $group->update(['lesson_ends_at' => $window->endsAt]);

            foreach ($group->addresses as $address) {
                if ($address->pinIsValid($now) && ! $address->hasOwnPin($running->endsAt, $now)) {
                    $this->pins->setExpiry($address, $window->endsAt);
                }
            }
        });
    }

    /** Ends the lesson at once: the PINs of the lesson become invalid and their plaintext is dropped. Own PINs stay. */
    public function end(Group $group, int $now): void
    {
        DB::transaction(function () use ($group, $now) {
            $previousEnd = $group->lesson_ends_at;
            $window = $group->window()?->end($now);

            if ($window !== null) {
                $group->update(['lesson_ends_at' => $window->endsAt]);
            }

            foreach ($group->addresses as $address) {
                if (! $address->hasOwnPin($previousEnd, $now)) {
                    $this->pins->revoke($address, $now);
                }
            }
        });
    }

    public function regenerateGroupPins(Group $group, int $now): void
    {
        $window = $this->runningWindow($group, $now);

        DB::transaction(function () use ($group, $window, $now) {
            foreach ($group->addresses as $address) {
                if (! $address->hasOwnPin($window->endsAt, $now)) {
                    $this->pins->issue($address, $window->endsAt);
                }
            }
        });
    }

    /**
     * Gives one address its own PIN, independent of the lessons of its group: valid for the PIN maximum
     * (MAX_PIN_DAYS) or until the teacher removes it. Removing it hands the address back to the group.
     */
    public function regenerateAddressPin(Address $address, int $now): void
    {
        $this->pins->issue($address, $now + config('lemurmail.max_pin_seconds'));
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
