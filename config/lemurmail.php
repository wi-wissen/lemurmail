<?php

return [
    // Domain of the temporary mailboxes (the app's own subdomain).
    'mail_domain' => env('MAIL_DOMAIN', 'tmp-mail.example.org'),

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

    // Validity of a single regenerated PIN when its group has no running lesson.
    'single_pin_seconds' => 45 * 60,

    // PIN brute-force protection.
    'pin_max_attempts' => 5,        // per address (and input) and IP
    'pin_ip_max_attempts' => 30,    // per IP over all addresses (a whole class may share one school IP)
    'pin_decay_seconds' => 15 * 60,

    'locales' => ['de', 'en'],
    'fallback_locale' => 'de',

    // Polling interval of the inbox in seconds.
    'poll_seconds' => 10,
];
