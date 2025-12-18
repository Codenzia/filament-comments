<?php

namespace Codenzia\FilamentComments\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class UserMentionedNotification extends Notification
{
    use Queueable;

    public $comment;
    public $byUser;

    public function __construct($comment, $byUser)
    {
        $this->comment = $comment;
        $this->byUser = $byUser;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->greeting('Hello!')
            ->line('You were mentioned in a comment by ' . $this->byUser->name)
            ->line('Comment: ' . $this->comment)
            ->action('View Comment', url('/'));
    }

    public function toArray($notifiable)
    {
        return [
            'by_user_id' => $this->byUser->id,
            'by_user_name' => $this->byUser->name,
            'comment' => $this->comment,
        ];
    }
}
