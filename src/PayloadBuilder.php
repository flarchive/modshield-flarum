<?php

namespace ModShield\Flarum;

class PayloadBuilder
{
    private const CONNECTOR_VERSION = '1.1.0';

    /** Flarum's default "Mods" group id. */
    public const DEFAULT_MODERATOR_GROUP_ID = 4;

    public static function fromPost(object $post, string $eventType, int $moderatorGroupId = self::DEFAULT_MODERATOR_GROUP_ID): array
    {
        $user = $post->user;
        $discussion = $post->discussion;

        return [
            'event_id' => 'flarum_post_' . $post->id,
            'platform' => 'flarum',
            'event_type' => $eventType,
            'occurred_at' => $post->created_at ? $post->created_at->toIso8601String() : date('c'),
            'actor' => [
                'id' => 'user_' . $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'ip' => $post->ip_address ?? null,
                'created_at' => $user->joined_at?->toIso8601String(),
                'role' => self::getUserRole($user, $moderatorGroupId),
                'posts_count' => $user->comment_count ?? 0,
            ],
            'content' => [
                'id' => 'post_' . $post->id,
                'type' => 'forum_post',
                'title' => ($post->number ?? 0) === 1 ? $discussion->title : null,
                'body' => $post->content ?? '',
                'links' => self::extractLinks($post->content ?? ''),
                'language' => null,
            ],
            'context' => [
                'discussion_id' => 'discussion_' . $discussion->id,
                'is_first_post' => ($post->number ?? 0) === 1,
            ],
            'metadata' => [
                'connector_version' => self::CONNECTOR_VERSION,
            ],
        ];
    }

    public static function feedbackPayload(int $postId, string $feedbackType): array
    {
        return [
            'event_id' => 'flarum_post_' . $postId,
            'feedback' => $feedbackType,
        ];
    }

    private static function getUserRole(object $user, int $moderatorGroupId = self::DEFAULT_MODERATOR_GROUP_ID): string
    {
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return 'admin';
        }
        if (isset($user->groups) && $user->groups->contains('id', $moderatorGroupId)) {
            return 'moderator';
        }
        return 'member';
    }

    public static function extractLinks(string $content): array
    {
        preg_match_all('#https?://[^\s<>"\')\]]+#i', $content, $matches);
        $links = [];
        foreach ($matches[0] as $url) {
            $domain = parse_url($url, PHP_URL_HOST);
            if ($domain) {
                $links[] = ['url' => $url, 'domain' => $domain];
            }
        }
        return $links;
    }
}
