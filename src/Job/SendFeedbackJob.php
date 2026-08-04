<?php

namespace ModShield\Flarum\Job;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use ModShield\Flarum\ModShieldClientFactory;
use ModShield\Flarum\ModShieldSettings;
use ModShield\Flarum\PayloadBuilder;

/**
 * Sends moderator feedback (spam / false_positive) to ModShield Core on the
 * queue, so moderation actions are not slowed down by the HTTP call.
 */
class SendFeedbackJob implements ShouldQueue
{
    use Queueable;
    use InteractsWithQueue;
    use SerializesModels;

    public function __construct(
        private int $postId,
        private string $feedbackType,
    ) {}

    public function handle(ModShieldSettings $settings, ModShieldClientFactory $factory): void
    {
        if ($settings->mode() === 'disabled') {
            return;
        }

        $client = $factory->make();
        if ($client === null) {
            return;
        }

        $client->feedback(PayloadBuilder::feedbackPayload($this->postId, $this->feedbackType));
    }
}
