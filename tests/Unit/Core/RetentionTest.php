<?php

use App\Core\Retention\RetentionPolicy;

it('computes the cutoff in whole seconds', function () {
    expect(RetentionPolicy::cutoff(1_000_000, 7))->toBe(1_000_000 - 7 * 86400);
    expect(RetentionPolicy::cutoff(1_000_000, 3))->toBe(1_000_000 - 3 * 86400);
});

it('never keeps longer than 7 days and at least 1 day', function () {
    expect(RetentionPolicy::cutoff(1_000_000, 30))->toBe(1_000_000 - 7 * 86400);
    expect(RetentionPolicy::cutoff(1_000_000, 0))->toBe(1_000_000 - 86400);
    expect(RetentionPolicy::cutoff(1_000_000, -4))->toBe(1_000_000 - 86400);
});
