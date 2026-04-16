<?php

namespace Codenzia\FilamentComments\Console\Commands;

use App\Models\User;
use Codenzia\FilamentComments\Models\Comment;
use Codenzia\FilamentComments\Models\CommentWatch;
use Codenzia\FilamentComments\Notifications\CommentDigestNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendCommentDigest extends Command
{
    protected $signature = 'filament-comments:send-digest';

    protected $description = 'Send a daily digest email of unread comments to watchers';

    public function handle(): int
    {
        if (! config('filament-comments.digest.enabled', false)) {
            $this->info('Comment digest is disabled. Enable it in config/filament-comments.php');

            return self::SUCCESS;
        }

        $userModel = config('filament-comments.user_model')
            ?? config('auth.providers.users.model', User::class);

        // Get all unique watcher user IDs
        $watcherIds = CommentWatch::distinct()->pluck('user_id');

        if ($watcherIds->isEmpty()) {
            $this->info('No watchers found.');

            return self::SUCCESS;
        }

        $sentCount = 0;

        foreach ($watcherIds as $userId) {
            $user = $userModel::find($userId);
            if (! $user) {
                continue;
            }

            // Get all watchable models for this user
            $watches = CommentWatch::where('user_id', $userId)->get();

            $unreadComments = collect();

            foreach ($watches as $watch) {
                $watchableClass = $watch->watchable_type;
                $watchableId = $watch->watchable_id;

                if (! class_exists($watchableClass)) {
                    continue;
                }

                // Get comments from the last 24 hours that are not by this user
                $comments = Comment::where('commentable_type', $watchableClass)
                    ->where('commentable_id', $watchableId)
                    ->where('user_id', '!=', $userId)
                    ->where('is_approved', true)
                    ->where('created_at', '>=', now()->subDay())
                    ->with('commentator')
                    ->get();

                if ($comments->isNotEmpty()) {
                    $watchable = $watchableClass::find($watchableId);
                    $unreadComments->push([
                        'watchable' => $watchable,
                        'watchable_type' => class_basename($watchableClass),
                        'comments' => $comments,
                    ]);
                }
            }

            if ($unreadComments->isEmpty()) {
                continue;
            }

            // Send notification
            try {
                $user->notify(new CommentDigestNotification($unreadComments));
                $sentCount++;
            } catch (\Throwable $e) {
                $this->error("Failed to send digest to user #{$userId}: {$e->getMessage()}");
            }
        }

        $this->info("Sent {$sentCount} digest email(s).");

        return self::SUCCESS;
    }
}
