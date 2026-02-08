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
        'icon',
        'visibility',
        'project_id',
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

    public function members()
    {
        $userModel = config('codenzia-comments.user_model') ?? config('auth.providers.users.model', \App\Models\User::class);

        return $this->belongsToMany(
            $userModel,
            config('codenzia-comments.channel_members_table_name', 'comment_channel_members'),
            'channel_id',
            'user_id'
        )->withTimestamps();
    }
}
