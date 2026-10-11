<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/** Human readable labels for Unix timestamps, in the app timezone. Display only, no logic. */
final class TimeLabel
{
    /**
     * "22:43" when the instant is on the same calendar day as $now, "18.10. 23:59" within the
     * same year and "18.10.2027 23:59" otherwise (English: "Oct 18, 23:59" / "Oct 18, 2027, 23:59").
     */
    public static function until(int $timestamp, int $now, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $at = Carbon::createFromTimestamp($timestamp, config('app.timezone'));
        $today = Carbon::createFromTimestamp($now, config('app.timezone'));
        $english = $locale === 'en';

        if ($at->isSameDay($today)) {
            return $at->format('H:i');
        }

        if ($at->year === $today->year) {
            return $at->format($english ? 'M j, H:i' : 'd.m. H:i');
        }

        return $at->format($english ? 'M j, Y, H:i' : 'd.m.Y H:i');
    }
}
