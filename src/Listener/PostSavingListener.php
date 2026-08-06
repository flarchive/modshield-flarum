<?php

namespace ModShield\Flarum\Listener;

use Flarum\Post\Event\Saving;
use ModShield\Flarum\CaptureStash;

/**
 * Reads the capture attributes the forum JS attaches to the post request and
 * stashes them on the post instance (transient — read back in the same
 * request by PostCreated/PostUpdated listeners). Attributes absent entirely
 * (no-JS client, direct API call) still produce a capture array with a null
 * token: on a JS-only platform the absence of instrumentation is itself a
 * signal ModShield Core should score.
 */
class PostSavingListener
{
    public function handle(Saving $event): void
    {
        $attributes = $event->data['attributes'] ?? [];

        $fillMs = $attributes['modshieldFillMs'] ?? null;

        CaptureStash::put($event->post, [
            'token' => isset($attributes['modshieldToken']) && is_string($attributes['modshieldToken'])
                ? $attributes['modshieldToken'] : null,
            'fill_ms' => is_numeric($fillMs) ? (int) $fillMs : null,
            'honeypot' => isset($attributes['modshieldHoneypot']) && is_string($attributes['modshieldHoneypot'])
                ? $attributes['modshieldHoneypot'] : null,
        ]);
    }
}
