<?php

namespace ModShield\Flarum;

/**
 * Transient per-request storage for capture attributes, keyed by the Post
 * object's spl_object_id. Exists because writing capture data onto the
 * Eloquent Post model directly (e.g. `$post->modshieldCapture = [...]`)
 * corrupts the model: Model::__set() puts unknown keys into the attribute
 * bag, and the subsequent save() then tries to write a nonexistent
 * `modshieldCapture` column, throwing a SQL exception. Keeping the capture
 * data out of band avoids touching the model's attributes at all.
 */
class CaptureStash
{
    private static array $stash = [];

    public static function put(object $post, array $capture): void
    {
        self::$stash[spl_object_id($post)] = $capture;
    }

    /**
     * Returns the stashed capture array for $post and removes it, or null
     * if nothing was stashed for this object.
     */
    public static function pull(object $post): ?array
    {
        $id = spl_object_id($post);

        if (!array_key_exists($id, self::$stash)) {
            return null;
        }

        $capture = self::$stash[$id];
        unset(self::$stash[$id]);

        return $capture;
    }
}
