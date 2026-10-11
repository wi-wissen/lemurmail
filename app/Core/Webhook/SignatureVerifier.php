<?php

namespace App\Core\Webhook;

/**
 * Lettermint: header "X-Lettermint-Signature: t=<unix seconds>,v1=<hex>" where
 * v1 = hash_hmac('sha256', "<t>.<raw body>", $secret). Reject if |now - t| > $tolerance.
 * Comparison must be constant-time. Malformed headers, empty secret or empty header => false.
 * Multiple v1= values may appear; any match passes.
 */
final class SignatureVerifier
{
    public static function verify(string $header, string $rawBody, string $secret, int $now, int $tolerance = 300): bool
    {
        if ($header === '' || $secret === '') {
            return false;
        }

        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $header) as $part) {
            if (! str_contains($part, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $part, 2);
            if ($key === 't' && $timestamp === null) {
                $timestamp = $value;
            }
            if ($key === 'v1') {
                $signatures[] = $value;
            }
        }

        if ($timestamp === null || ! ctype_digit($timestamp) || abs($now - (int) $timestamp) > $tolerance) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$rawBody, $secret);
        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    /** Builds a valid header (used by tests and `mail:simulate`). */
    public static function sign(string $rawBody, string $secret, int $timestamp): string
    {
        return 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$rawBody, $secret);
    }
}
