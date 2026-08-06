<?php

namespace ModShield\Flarum;

/**
 * Verifies ModShield Core signed callbacks (Stripe-style scheme):
 *   X-ModShield-Signature: t=<unix>,v1=<hex hmac_sha256("{t}.{body}", secret)>
 * Multiple v1= entries are accepted (secret rotation). PHP 8.0-safe on
 * purpose; mirrors the SDK CallbackVerifier without depending on it.
 */
class CallbackSignature
{
    public static function verify(
        string $rawBody,
        string $header,
        string $secret,
        int $toleranceSeconds = 300,
        ?int $now = null
    ): bool {
        if ($rawBody === '' || $header === '' || $secret === '') {
            return false;
        }

        $timestamp = null;
        $candidates = [];
        foreach (explode(',', $header) as $part) {
            $pieces = explode('=', trim($part), 2);
            if (count($pieces) !== 2) {
                continue;
            }
            if ($pieces[0] === 't' && ctype_digit($pieces[1])) {
                $timestamp = (int) $pieces[1];
            } elseif ($pieces[0] === 'v1' && $pieces[1] !== '') {
                $candidates[] = $pieces[1];
            }
        }

        if ($timestamp === null || $candidates === []) {
            return false;
        }

        $now = $now ?? time();
        if (abs($now - $timestamp) > $toleranceSeconds) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret);
        foreach ($candidates as $candidate) {
            if (hash_equals($expected, $candidate)) {
                return true;
            }
        }

        return false;
    }
}
