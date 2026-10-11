<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/** The only place that reads the current time. Returns whole Unix seconds. */
final class Clock
{
    public static function now(): int
    {
        return Carbon::now()->getTimestamp();
    }
}
