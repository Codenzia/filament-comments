<?php

namespace Codenzia\FilamentComments\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;

class CommentReaction extends Model
{
    protected $fillable = [
        'comment_id',
        'user_id',
        'reaction_type',
    ];

    public function getTable()
    {
        return config('codenzia-comments.reactions_table_name', 'comments_reactions');
    }

    public function comment()
    {
        return $this->belongsTo(Comment::class);
    }

    public function user()
    {
        return $this->belongsTo($this->getAuthModelName(), 'user_id');
    }

    protected function getAuthModelName()
    {
        if (config('filament-comments.user_model')) {
            return config('filament-comments.user_model');
        }

        if (! is_null(config('auth.providers.users.model'))) {
            return config('auth.providers.users.model');
        }

        throw new Exception('Could not determine the user model name.');
    }
}
