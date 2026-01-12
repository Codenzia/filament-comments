<?php

namespace Codenzia\FilamentComments\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

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
        $commentText = strip_tags($this->comment);

        return (new MailMessage)
            ->greeting('Hello!')
            ->line('You were mentioned in a comment by ' . $this->byUser->name)
            ->line('Comment: ' . $commentText)
            ->action('View Comment', url('/'));
    }

    public function toDatabase(object $notifiable): array
    {
        $body = 'You were mentioned in a comment by ' . $this->byUser->name . ' in the comment: ' . strip_tags($this->comment);

        return [
            'title' => 'You were mentioned in a comment',
            'body' => $body,
            'icon' => 'heroicon-o-clipboard-document',
            'color' => 'info',
            'duration' => 'persistent',
            'format' => 'filament',
            'actions' => [
                [
                    'name' => 'View Comment',
                    'url' => '#',
                ],
            ],
        ];
    }

    public function toArray($notifiable)
    {
        $body = 'You were mentioned in a comment by ' . $this->byUser->name . ' in the comment: ' . strip_tags($this->comment);

        return [
            'title' => 'You were mentioned in a comment',
            'body' => $body,
            'icon' => 'heroicon-o-clipboard-document',
        ];
    }
}
