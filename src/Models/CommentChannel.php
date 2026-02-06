<?php

namespace Codenzia\FilamentComments\Models;

use Illuminate\Database\Eloquent\Model;

class CommentChannel extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    public static function boot()
    {
        parent::boot();
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
