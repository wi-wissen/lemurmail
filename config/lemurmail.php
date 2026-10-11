<?php

return [
    // Domain of the temporary mailboxes (the app's own subdomain).
    'mail_domain' => env('MAIL_DOMAIN', 'tmp-mail.example.org'),

    // How strictly the sender of a whitelisted mail must be authenticated: aligned (default) | authenticated | off.
    // See App\Core\Mail\AuthMode. Relax it if mail of a legitimate service does not arrive.
    'inbound_auth' => env('INBOUND_AUTH_MODE', 'aligned'),

    'webhook_secret' => env('LETTERMINT_WEBHOOK_SECRET'),
    'webhook_tolerance' => 300,

    'admin_email' => env('ADMIN_EMAIL'),

    // Retention of stored mails in days; hard-capped at 7 by RetentionPolicy.
    'retention_days' => (int) env('MAIL_RETENTION_DAYS', 7),

    // Default cap of addresses per teacher (Super-Admin can override globally or per teacher).
    'default_address_limit' => (int) env('DEFAULT_ADDRESS_LIMIT', 200),

    // Longest allowed lesson / PIN validity in seconds.
    'max_lesson_seconds' => (int) env('MAX_LESSON_MINUTES', 720) * 60,
    'extend_seconds' => 15 * 60,

    // Longest validity when a lesson/PINs are started with an end DATE (there is deliberately no unlimited option).
    'max_pin_seconds' => (int) env('MAX_PIN_DAYS', 365) * 86400,

    // Validity of a single regenerated PIN when its group has no running lesson.
    'single_pin_seconds' => 45 * 60,

    // PIN brute-force protection. Every login also needs the Friendly Captcha.
    // Failures are counted per browser session and, as a high circuit breaker, per address. A per-IP limit is
    // OFF by default (0): a whole school often shares one IP and one pupil must not lock out everybody.
    'pin_max_attempts' => 5,            // failures per browser session
    'pin_address_max_attempts' => 50,   // failures per address over all sessions
    'pin_ip_max_attempts' => (int) env('PIN_IP_MAX_ATTEMPTS', 0), // 0 = no IP limit
    'pin_decay_seconds' => 15 * 60,

    // Legal pages of the operator (full URLs). The footer links only appear when set.
    'imprint_url' => env('IMPRINT_URL'),
    'privacy_url' => env('PRIVACY_URL'),

    'locales' => ['de', 'en'],
    'fallback_locale' => 'de',

    // Polling interval of the inbox in seconds.
    'poll_seconds' => 10,
];
