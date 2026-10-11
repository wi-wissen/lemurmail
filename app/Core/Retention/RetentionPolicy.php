<?php

namespace App\Core\Retention;

final class RetentionPolicy
{
    public const MAX_DAYS = 7;

    /** Mails with received_at < the returned instant are due for deletion. Days are clamped to 1..MAX_DAYS. */
    public static function cutoff(int $now, int $configuredDays): int
    {
        $days = max(1, min(self::MAX_DAYS, $configuredDays));

        return $now - $days * 86400;
    }
}
