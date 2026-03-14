<?php

namespace Codenzia\FilamentComments\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommentReaction extends Model
{
    protected $fillable = [
        'comment_id',
        'user_id',
        'reaction_type',
    ];

    protected $casts = [
        'comment_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function getTable(): string
    {
        return config('filament-comments.reactions_table_name', 'comments_reactions');
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo($this->getAuthModelName(), 'user_id');
    }

    /**
     * @return class-string<Model>
     */
    protected function getAuthModelName(): string
    {
        if (config('filament-comments.user_model')) {
            return config('filament-comments.user_model');
        }

        return config('auth.providers.users.model', User::class);
    }
}
