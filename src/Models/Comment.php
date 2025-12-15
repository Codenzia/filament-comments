<?php

namespace Codenzia\FilamentComments\Models;

use Codenzia\FilamentComments\Events\CommentAdded;
use Codenzia\FilamentComments\Events\CommentDeleted;
use Codenzia\FilamentComments\Traits\HasComments;
use Exception;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasComments;

    protected $fillable = [
        'comment',
        'user_id',
        'is_approved',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
    ];

    public function getTable()
    {
        return config('codenzia-comments.table_name', 'comments');
    }

    public static function boot(): void
    {
        parent::boot();

        static::deleting(function (self $model) {
            if (config('filament-comments.delete_replies_along_comments')) {
                $model->comments()->delete();
            }
        });

        static::deleted(function (self $model) {
            CommentDeleted::dispatch($model);
        });

        static::created(function (self $model) {
            CommentAdded::dispatch($model);
        });
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function commentable()
    {
        return $this->morphTo();
    }

    public function commentator()
    {
        return $this->belongsTo($this->getAuthModelName(), 'user_id');
    }

    public function approve()
    {
        $this->update([
            'is_approved' => true,
        ]);

        return $this;
    }

    public function disapprove()
    {
        $this->update([
            'is_approved' => false,
        ]);

        return $this;
    }

    protected function getAuthModelName()
    {
        if (config('filament-comments.user_model')) {
            return config('filament-comments.user_model');
        }

        if (! is_null(config('auth.providers.users.model'))) {
            return config('auth.providers.users.model');
        }

        throw new Exception('Could not determine the commentator model name.');
    }
}
