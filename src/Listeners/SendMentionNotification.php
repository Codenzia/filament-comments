<?php

namespace Codenzia\FilamentComments\Listeners;

use Codenzia\FilamentComments\Events\UserMentioned;
use Codenzia\FilamentComments\Notifications\UserMentionedNotification;

class SendMentionNotification
{
    public function handle(UserMentioned $event)
    {
        $event->mentionedUser->notify(new UserMentionedNotification($event->comment, $event->byUser));
    }
}
