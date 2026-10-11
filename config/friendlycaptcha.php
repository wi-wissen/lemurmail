<?php

return [
    'enabled' => (bool) env('FRIENDLY_CAPTCHA_ENABLED', true),
    'secret' => env('FRIENDLY_CAPTCHA_SECRET'),
    'sitekey' => env('FRIENDLY_CAPTCHA_SITEKEY'),
    'puzzle_endpoint' => env('FRIENDLY_CAPTCHA_PUZZLE_ENDPOINT', 'https://api.friendlycaptcha.com/api/v1/puzzle'),
    'verify_endpoint' => env('FRIENDLY_CAPTCHA_VERIFY_ENDPOINT', 'https://api.friendlycaptcha.com/api/v1/siteverify'),
];
