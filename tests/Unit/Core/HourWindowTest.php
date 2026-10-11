<?php

use App\Core\Hour\HourWindow;

const H_NOW = 1_000_000;
const H_MAX = 12 * 3600;

it('starts a window with a duration', function () {
    $w = HourWindow::start(H_NOW, 45 * 60, H_MAX);
    expect($w->startedAt)->toBe(H_NOW)->and($w->endsAt)->toBe(H_NOW + 2700);
    expect($w->isActive(H_NOW))->toBeTrue()
        ->and($w->isActive(H_NOW + 2699))->toBeTrue()
        ->and($w->isActive(H_NOW + 2700))->toBeFalse()
        ->and($w->isActive(H_NOW - 1))->toBeFalse();
    expect($w->remaining(H_NOW + 60))->toBe(2640)->and($w->remaining(H_NOW + 9999))->toBe(0);
});

it('rejects invalid durations', function (int $d) {
    HourWindow::start(H_NOW, $d, H_MAX);
})->with([0, -5, H_MAX + 1])->throws(InvalidArgumentException::class);

it('accepts the maximum duration', function () {
    expect(HourWindow::start(H_NOW, H_MAX, H_MAX)->endsAt)->toBe(H_NOW + H_MAX);
});

it('starts until a timestamp', function () {
    expect(HourWindow::until(H_NOW, H_NOW + 5000, H_MAX)->endsAt)->toBe(H_NOW + 5000);
});

it('rejects invalid end timestamps', function (int $end) {
    HourWindow::until(H_NOW, $end, H_MAX);
})->with([H_NOW, H_NOW - 1, H_NOW + H_MAX + 1])->throws(InvalidArgumentException::class);

it('extends by 15 minutes', function () {
    $w = HourWindow::start(H_NOW, 2700, H_MAX)->extend(H_NOW + 100, 900, H_MAX);
    expect($w->startedAt)->toBe(H_NOW)->and($w->endsAt)->toBe(H_NOW + 3600);
});

it('clamps extension to the maximum from the start', function () {
    $w = HourWindow::start(H_NOW, H_MAX - 100, H_MAX)->extend(H_NOW + 10, 900, H_MAX);
    expect($w->endsAt)->toBe(H_NOW + H_MAX);
});

it('restarts an expired window when extended', function () {
    $w = HourWindow::start(H_NOW, 600, H_MAX);
    $later = H_NOW + 5000;
    $e = $w->extend($later, 900, H_MAX);
    expect($e->startedAt)->toBe($later)->and($e->endsAt)->toBe($later + 900);
});

it('ends immediately', function () {
    $w = HourWindow::start(H_NOW, 3600, H_MAX);
    $e = $w->end(H_NOW + 60);
    expect($e->endsAt)->toBe(H_NOW + 60)->and($e->isActive(H_NOW + 60))->toBeFalse()->and($e->startedAt)->toBe(H_NOW);
    expect($e->end(H_NOW + 999)->endsAt)->toBe(H_NOW + 60);
    expect($w->endsAt)->toBe(H_NOW + 3600); // immutable
});
