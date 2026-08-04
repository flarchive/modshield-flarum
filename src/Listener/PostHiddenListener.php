<?php

namespace ModShield\Flarum\Listener;

use Flarum\Post\Event\Hidden;
use Illuminate\Contracts\Bus\Dispatcher;
use ModShield\Flarum\Job\SendFeedbackJob;
use ModShield\Flarum\ModShieldSettings;

class PostHiddenListener
{
    public function __construct(
        private ModShieldSettings $settings,
        private Dispatcher $bus,
    ) {}

    public function handle(Hidden $event): void
    {
        if (!$this->settings->isEnabled()) {
            return;
        }

        if (!$event->actor || (!$event->actor->isAdmin() && !$event->actor->hasPermission('discussion.hide'))) {
            return;
        }

        $this->bus->dispatch(new SendFeedbackJob($event->post->id, 'spam'));
    }
}
