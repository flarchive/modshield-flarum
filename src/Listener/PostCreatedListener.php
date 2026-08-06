<?php

namespace ModShield\Flarum\Listener;

use Flarum\Post\Event\Posted;
use Illuminate\Contracts\Bus\Dispatcher;
use ModShield\Flarum\CaptureStash;
use ModShield\Flarum\Job\CheckPostJob;
use ModShield\Flarum\ModShieldSettings;

class PostCreatedListener
{
    public function __construct(
        private ModShieldSettings $settings,
        private Dispatcher $bus,
    ) {}

    public function handle(Posted $event): void
    {
        if (!$this->settings->isEnabled() || !$this->settings->isConfigured()) {
            return;
        }

        $this->bus->dispatch(new CheckPostJob(
            $event->post->id,
            'content.created',
            null,
            CaptureStash::pull($event->post)
        ));
    }
}
