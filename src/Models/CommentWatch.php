<?php

namespace Codenzia\FilamentComments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CommentWatch extends Model
{
    protected $fillable = [
        'user_id',
        'watchable_type',
        'watchable_id',
    ];

    public function getTable(): string
    {
        return config('filament-comments.watches_table_name', 'comment_watches');
    }

    public function watchable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        $userModel = config('filament-comments.user_model')
            ?? config('auth.providers.users.model', \App\Models\User::class);

        return $this->belongsTo($userModel);
    }
}
