<?php

namespace Codenzia\FilamentComments\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CommentDigestNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Collection $groups,
    ) {}

    /**
     * @return array<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $totalComments = $this->groups->sum(fn ($g) => $g['comments']->count());

        $message = (new MailMessage)
            ->subject("You have {$totalComments} new comment(s)")
            ->greeting("Hi {$notifiable->name},")
            ->line("Here's a summary of new comments from the last 24 hours:");

        foreach ($this->groups as $group) {
            $watchableType = $group['watchable_type'];
            $watchable = $group['watchable'];
            $title = $watchable?->title ?? $watchable?->name ?? "#{$watchable?->id}";

            $message->line("**{$watchableType}: {$title}** ({$group['comments']->count()} new)");

            foreach ($group['comments']->take(5) as $comment) {
                $author = $comment->commentator?->name ?? 'Unknown';
                $preview = Str::limit(strip_tags($comment->comment), 80);
                $message->line("- {$author}: {$preview}");
            }

            if ($group['comments']->count() > 5) {
                $remaining = $group['comments']->count() - 5;
                $message->line("... and {$remaining} more");
            }
        }

        $message->line('Visit the app to view and respond to comments.');

        return $message;
    }
}
