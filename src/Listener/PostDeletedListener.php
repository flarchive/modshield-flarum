<?php

namespace ModShield\Flarum\Listener;

use Flarum\Post\Event\Deleted;
use Illuminate\Contracts\Bus\Dispatcher;
use ModShield\Flarum\Job\SendFeedbackJob;
use ModShield\Flarum\ModShieldSettings;

class PostDeletedListener
{
    public function __construct(
        private ModShieldSettings $settings,
        private Dispatcher $bus,
    ) {}

    public function handle(Deleted $event): void
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
