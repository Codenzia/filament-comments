<?php

namespace Codenzia\FilamentComments\Models;

use App\Models\User;
use Codenzia\FilamentComments\Enums\CommentType;
use Codenzia\FilamentComments\Events\CommentAdded;
use Codenzia\FilamentComments\Events\CommentDeleted;
use Codenzia\FilamentComments\Traits\HasComments;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;

class Comment extends Model
{
    use HasComments;

    protected $fillable = [
        'comment',
        'link_previews',
        'type',
        'user_id',
        'channel_id',
        'is_approved',
        'is_pinned',
        'is_resolved',
        'resolved_by',
        'linked_task_id',
        'parent_id',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'is_pinned' => 'boolean',
        'is_resolved' => 'boolean',
        'link_previews' => 'array',
        'type' => CommentType::class,
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(CommentChannel::class, 'channel_id');
    }

    public function getTable(): string
    {
        return config('filament-comments.table_name', 'comments');
    }

    public static function boot(): void
    {
        parent::boot();

        static::deleting(function (self $model): void {
            if (config('filament-comments.delete_replies_along_comments')) {
                $model->comments()->delete();
            }
        });

        static::deleted(function (self $model): void {
            CommentDeleted::dispatch($model);
        });

        static::created(function (self $model): void {
            CommentAdded::dispatch($model);
        });
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true);
    }

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function commentator(): BelongsTo
    {
        return $this->belongsTo($this->getAuthModelName(), 'user_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->approved()->with(['commentator', 'replies']);
    }

    public function isReply(): bool
    {
        return ! is_null($this->parent_id);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(CommentReaction::class);
    }

    public function userReaction(?int $userId = null): ?CommentReaction
    {
        $userId = $userId ?? auth()->id();

        return $this->reactions()->where('user_id', $userId)->first();
    }

    /**
     * @return array<string, int>
     */
    public function getReactionsSummary(): array
    {
        return $this->reactions()
            ->selectRaw('reaction_type, count(*) as count')
            ->groupBy('reaction_type')
            ->get()
            ->pluck('count', 'reaction_type')
            ->toArray();
    }

    public function isType(CommentType $type): bool
    {
        return $this->type === $type;
    }

    /**
     * Decode the comment body as JSON for vote/image types.
     *
     * @return array<string, mixed>
     */
    public function getDecodedComment(): array
    {
        $decoded = json_decode($this->comment, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function approve(): static
    {
        $this->update([
            'is_approved' => true,
        ]);

        return $this;
    }

    public function disapprove(): static
    {
        $this->update([
            'is_approved' => false,
        ]);

        return $this;
    }

    // ── Pin ──────────────────────────────────────────────────────

    public function scopePinned(Builder $query): Builder
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Pin this comment. Only one pinned comment per commentable — unpins the previous one.
     */
    public function pin(): static
    {
        DB::transaction(function () {
            // Unpin any existing pinned comment for the same commentable
            static::where('commentable_type', $this->commentable_type)
                ->where('commentable_id', $this->commentable_id)
                ->where('is_pinned', true)
                ->where('id', '!=', $this->id)
                ->update(['is_pinned' => false]);

            $this->update(['is_pinned' => true]);
        });

        return $this;
    }

    public function unpin(): static
    {
        $this->update(['is_pinned' => false]);

        return $this;
    }

    // ── Resolve ──────────────────────────────────────────────────

    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('is_resolved', true);
    }

    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('is_resolved', false)->orWhereNull('is_resolved');
        });
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo($this->getAuthModelName(), 'resolved_by');
    }

    /**
     * Resolve this comment thread. Only root comments can be resolved.
     */
    public function resolve(?int $userId = null): static
    {
        if ($this->isReply()) {
            return $this;
        }

        $this->update([
            'is_resolved' => true,
            'resolved_by' => $userId ?? auth()->id(),
        ]);

        return $this;
    }

    public function unresolve(): static
    {
        $this->update([
            'is_resolved' => false,
            'resolved_by' => null,
        ]);

        return $this;
    }

    // ── Linked Task ──────────────────────────────────────────────

    public function linkedTask(): BelongsTo
    {
        $taskModel = config('filament-comments.task_mentionable.model');

        return $this->belongsTo($taskModel ?? 'App\\Models\\Task', 'linked_task_id');
    }

    // ── Bookmarks ────────────────────────────────────────────────

    public function bookmarks(): HasMany
    {
        return $this->hasMany(CommentBookmark::class);
    }

    public function isBookmarkedBy(?int $userId = null): bool
    {
        $userId = $userId ?? auth()->id();

        return $this->bookmarks()->where('user_id', $userId)->exists();
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
