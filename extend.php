<?php

use Flarum\Extend;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Revised;
use Flarum\Post\Event\Hidden;
use Flarum\Post\Event\Restored;
use Flarum\Post\Event\Deleted;
use ModShield\Flarum\Listener;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__ . '/js/dist/admin.js'),

    (new Extend\Locales(__DIR__ . '/resources/locale')),

    (new Extend\Event())
        ->listen(Posted::class, Listener\PostCreatedListener::class)
        ->listen(Revised::class, Listener\PostUpdatedListener::class)
        ->listen(Hidden::class, Listener\PostHiddenListener::class)
        ->listen(Restored::class, Listener\PostRestoredListener::class)
        ->listen(Deleted::class, Listener\PostDeletedListener::class),
];
