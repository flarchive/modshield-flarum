<?php

namespace ModShield\Flarum;

class ActionApplier
{
    /**
     * Apply a ModShield decision to a post.
     *
     * Returns the action that was actually enforced ('allow', 'send_to_review'
     * or 'block'), or null when the mode is not enforcing (shadow/disabled).
     */
    public static function apply(object $post, array $decision, string $mode): ?string
    {
        // Only 'active' enforces. 'disabled' short-circuits earlier; 'shadow' is
        // a legacy connector mode (observe-only is now driven by ModShield Core)
        // kept here so a leftover stored value stays a safe no-op.
        if ($mode === 'shadow' || $mode === 'disabled') {
            return null;
        }

        $action = $decision['effective_action'] ?? 'allow';

        switch ($action) {
            case 'block':
            case 'send_to_review':
                // Flarum cannot reject an already-created post, so both a hard
                // block and a review hold are enforced by hiding the post. The
                // distinct return value lets callers log/report which one it was.
                $post->hide();
                $post->save();
                return $action;

            default:
                return 'allow';
        }
    }
}
