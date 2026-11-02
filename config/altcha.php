<?php

return [
    // Turn the captcha on. Off only for local development and tests.
    'enabled' => (bool) env('ALTCHA_ENABLED', true),

    // Proof-of-work cost: PBKDF2 iterations per attempt, and the range the secret counter is drawn from.
    // The browser tries counters from 0 upwards, so (cost x counter) is the work it has to do (about a second).
    'cost' => (int) env('ALTCHA_COST', 2000),
    'counter_min' => (int) env('ALTCHA_COUNTER_MIN', 300),
    'counter_max' => (int) env('ALTCHA_COUNTER_MAX', 1200),

    // Seconds a challenge stays valid (a solved challenge can be used once).
    'ttl' => (int) env('ALTCHA_TTL', 600),

    // Optional. Default: derived from APP_KEY, so there is nothing to configure.
    'secret' => env('ALTCHA_SECRET'),
];
