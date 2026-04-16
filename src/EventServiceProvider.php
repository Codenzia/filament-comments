<?php

namespace Codenzia\FilamentComments;

use Codenzia\FilamentComments\Events\UserMentioned;
use Codenzia\FilamentComments\Listeners\SendMentionNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        UserMentioned::class => [
            SendMentionNotification::class,
        ],
    ];
}
