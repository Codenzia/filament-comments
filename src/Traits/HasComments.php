<?php

namespace Codenzia\FilamentComments\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasComments
{
    /**
     * Return all comments for this model.
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(config('filament-comments.comment_class') ?? \Codenzia\FilamentComments\Models\Comment::class, 'commentable');
    }

    /**
     * Attach a comment to this model.
     */
    public function comment(string $comment, ?string $type = null, bool $is_approved = true): Model
    {
        return $this->commentAsUser(auth()->user(), $comment, $type, $is_approved);
    }

    /**
     * Attach a comment to this model as a specific user.
     */
    public function commentAsUser(?Model $user, string $comment, ?string $type = null, bool $is_approved = true): Model
    {
        $commentClass = config('filament-comments.comment_class') ?? \Codenzia\FilamentComments\Models\Comment::class;

        $comment = new $commentClass([
            'comment' => $comment,
            'type' => $type,
            'is_approved' => $is_approved,
            'user_id' => is_null($user) ? null : $user->getKey(),
            'commentable_id' => $this->getKey(),
            'commentable_type' => get_class($this),
        ]);

        return $this->comments()->save($comment);
    }
}
