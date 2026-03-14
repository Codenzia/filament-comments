<?php

namespace Codenzia\FilamentComments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommentBookmark extends Model
{
    protected $fillable = [
        'user_id',
        'comment_id',
    ];

    public function getTable(): string
    {
        return config('filament-comments.bookmarks_table_name', 'comment_bookmarks');
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    public function user(): BelongsTo
    {
        $userModel = config('filament-comments.user_model')
            ?? config('auth.providers.users.model', \App\Models\User::class);

        return $this->belongsTo($userModel);
    }
}
