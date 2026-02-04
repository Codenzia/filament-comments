<?php

namespace Codenzia\FilamentComments\Models;

use Illuminate\Database\Eloquent\Model;

class CommentChannel extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_default',
        'permissions',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'permissions' => 'array',
    ];

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($channel) {
            if ($channel->is_default) {
                return false;
            }
        });
    }

    public function getTable()
    {
        return config('codenzia-comments.channels_table_name', 'comment_channels');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'channel_id');
    }
}
