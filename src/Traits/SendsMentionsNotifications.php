<?php

namespace Codenzia\FilamentComments\Traits;

use App\Notifications\UserMentioned;
use Illuminate\Support\Facades\Notification;

trait SendsMentionsNotifications
{
    public function sendMentionNotification($user, $message)
    {
        Notification::send($user, new UserMentioned($message));
    }
}