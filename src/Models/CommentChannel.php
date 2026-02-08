<?php

namespace Codenzia\FilamentComments\Models;

use Illuminate\Database\Eloquent\Model;

class CommentChannel extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'visibility',
        'project_id',
        'created_by',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($channel) {
            $channel->created_by = auth()->id();
        });

        static::created(function (CommentChannel $channel) {
            if (auth()->check() && ! $channel->members()->where('users.id', auth()->id())->exists()) {
                $channel->members()->attach(auth()->id());
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

    public function members()
    {
        $userModel = config('codenzia-comments.user_model') ?? config('auth.providers.users.model', \App\Models\User::class);
        $userInstance = new $userModel;

        return $this->belongsToMany(
            $userModel,
            config('codenzia-comments.channel_members_table_name', 'comment_channel_members'),
            'channel_id',
            'user_id'
        )->withTimestamps();
    }

    public function project()
    {
        $projectModel = config('codenzia-comments.project_model', \App\Models\Project::class);

        return $this->belongsTo($projectModel, 'project_id');
    }
}
