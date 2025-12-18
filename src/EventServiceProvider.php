<?php

namespace Codenzia\FilamentComments;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \Codenzia\FilamentComments\Events\UserMentioned::class => [
            \Codenzia\FilamentComments\Listeners\SendMentionNotification::class,
        ],
    ];
}
