<?php

namespace ModShield\Flarum\Listener;

use Flarum\Post\Event\Revised;
use Illuminate\Contracts\Bus\Dispatcher;
use ModShield\Flarum\CaptureStash;
use ModShield\Flarum\Job\CheckPostJob;
use ModShield\Flarum\ModShieldSettings;

class PostUpdatedListener
{
    public function __construct(
        private ModShieldSettings $settings,
        private Dispatcher $bus,
    ) {}

    public function handle(Revised $event): void
    {
        if (!$this->settings->isEnabled() || !$this->settings->isConfigured()) {
            return;
        }

        // A distinct event id per edit keeps revisions from colliding with the
        // original post's event in ModShield Core.
        $eventId = 'flarum_edit_' . $event->post->id . '_' . time();

        $this->bus->dispatch(new CheckPostJob(
            $event->post->id,
            'content.updated',
            $eventId,
            CaptureStash::pull($event->post)
        ));
    }
}
