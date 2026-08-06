<?php

namespace ModShield\Flarum;

/**
 * Pure decision logic for an already-signature-verified callback. Returns a
 * status string the controller echoes back; every status maps to HTTP 2xx so
 * ModShield Core stops retrying. The callback RECOMMENDS — mode gating keeps
 * the forum authoritative.
 */
class CallbackAction
{
    public function apply(array $payload, ?object $post, string $mode, ?int $now = null): string
    {
        $now = $now ?? time();

        $expiresAt = isset($payload['expires_at']) && is_string($payload['expires_at'])
            ? strtotime($payload['expires_at']) : false;
        if ($expiresAt !== false && $expiresAt < $now) {
            return 'expired';
        }

        if ($post === null) {
            return 'not_found';
        }

        if ($mode !== 'active') {
            return 'observed';
        }

        $action = $payload['recommended_action'] ?? 'allow';
        if (!in_array($action, ['block', 'send_to_review'], true)) {
            return 'noop';
        }

        if ($post->hidden_at !== null) {
            return 'noop';
        }

        ActionApplier::apply($post, ['effective_action' => $action], $mode);

        return 'applied';
    }
}
