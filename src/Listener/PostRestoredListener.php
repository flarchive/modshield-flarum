<?php

namespace ModShield\Flarum\Listener;

use Flarum\Post\Event\Restored;
use Illuminate\Contracts\Bus\Dispatcher;
use ModShield\Flarum\Job\SendFeedbackJob;
use ModShield\Flarum\ModShieldSettings;

class PostRestoredListener
{
    public function __construct(
        private ModShieldSettings $settings,
        private Dispatcher $bus,
    ) {}

    public function handle(Restored $event): void
    {
        if (!$this->settings->isEnabled()) {
            return;
        }

        $this->bus->dispatch(new SendFeedbackJob($event->post->id, 'false_positive'));
    }
}
