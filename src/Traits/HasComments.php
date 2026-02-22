<?php

namespace Codenzia\FilamentComments\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasComments
{
    /**
     * Return all comments for this model.
     *
     * @return MorphMany
     */
    public function comments()
    {
        return $this->morphMany(config('filament-comments.comment_class') ?? \Codenzia\FilamentComments\Models\Comment::class, 'commentable');
    }

    /**
     * Attach a comment to this model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function comment(string $comment, ?string $type = null, bool $is_approved = true)
    {
        return $this->commentAsUser(auth()->user(), $comment, $type, $is_approved);
    }

    /**
     * Attach a comment to this model as a specific user.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function commentAsUser(?Model $user, string $comment, ?string $type = null, bool $is_approved = true)
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
