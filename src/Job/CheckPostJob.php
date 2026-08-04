<?php

namespace ModShield\Flarum\Job;

use Flarum\Post\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ModShield\Flarum\ActionApplier;
use ModShield\Flarum\ModShieldClientFactory;
use ModShield\Flarum\ModShieldSettings;
use ModShield\Flarum\PayloadBuilder;

/**
 * Sends a post to ModShield Core for a decision and enforces it. Runs on
 * Flarum's queue so the HTTP round-trip no longer blocks the web request
 * (falls back to inline execution on the default sync queue).
 */
class CheckPostJob implements ShouldQueue
{
    use Queueable;
    use InteractsWithQueue;
    use SerializesModels;

    public function __construct(
        private int $postId,
        private string $eventType,
        private ?string $eventId = null,
    ) {}

    public function handle(ModShieldSettings $settings, ModShieldClientFactory $factory): void
    {
        $mode = $settings->mode();
        if ($mode === 'disabled') {
            return;
        }

        $client = $factory->make();
        if ($client === null) {
            return;
        }

        $post = Post::find($this->postId);
        if ($post === null) {
            return;
        }

        $payload = PayloadBuilder::fromPost($post, $this->eventType, $settings->moderatorGroupId());
        if ($this->eventId !== null) {
            $payload['event_id'] = $this->eventId;
        }

        $decision = $client->check($payload);

        if ($decision === null) {
            // Fail-closed only enforces in active mode; shadow stays observe-only
            // (ActionApplier is a no-op for shadow/disabled).
            if ($settings->failClosed()) {
                ActionApplier::apply($post, ['effective_action' => 'send_to_review'], $mode);
            }
            return;
        }

        ActionApplier::apply($post, $decision, $mode);
    }
}
