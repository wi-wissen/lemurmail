<?php

use App\Support\TimeLabel;
use Illuminate\Support\Carbon;

function ts(string $local): int
{
    return Carbon::parse($local, 'Europe/Berlin')->getTimestamp();
}

it('shows only the clock time for the same day', function () {
    expect(TimeLabel::until(ts('2026-10-11 22:43'), ts('2026-10-11 09:00'), 'de'))->toBe('22:43');
    expect(TimeLabel::until(ts('2026-10-11 22:43'), ts('2026-10-11 09:00'), 'en'))->toBe('22:43');
});

it('adds the day for another day of the same year', function () {
    expect(TimeLabel::until(ts('2026-11-18 23:59'), ts('2026-10-11 09:00'), 'de'))->toBe('18.11. 23:59');
    expect(TimeLabel::until(ts('2026-11-18 23:59'), ts('2026-10-11 09:00'), 'en'))->toBe('Nov 18, 23:59');
});

it('adds the year for another year', function () {
    expect(TimeLabel::until(ts('2027-01-05 23:59'), ts('2026-10-11 09:00'), 'de'))->toBe('05.01.2027 23:59');
    expect(TimeLabel::until(ts('2027-01-05 23:59'), ts('2026-10-11 09:00'), 'en'))->toBe('Jan 5, 2027, 23:59');
});

it('uses the app timezone and the app locale by default', function () {
    app()->setLocale('de');
    expect(TimeLabel::until(ts('2026-10-11 00:30'), ts('2026-10-11 23:00')))->toBe('00:30');
});
