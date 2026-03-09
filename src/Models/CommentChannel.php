<?php

namespace Codenzia\FilamentComments\Models;

use Codenzia\FilamentComments\Enums\ChannelType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class CommentChannel extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'visibility',
        'type',
        'project_id',
        'created_by',
        'show_sidebar',
    ];

    protected $casts = [
        'show_sidebar' => 'boolean',
        'project_id' => 'integer',
        'created_by' => 'integer',
        'type' => ChannelType::class,
    ];

    public static function boot(): void
    {
        parent::boot();

        static::creating(function (self $channel): void {
            if (! $channel->created_by) {
                $channel->created_by = auth()->id();
            }
        });

        static::created(function (self $channel): void {
            if (auth()->check() && ! $channel->channelMembers()->where('users.id', auth()->id())->exists()) {
                $channel->channelMembers()->attach(auth()->id());
            }
        });
    }

    public function getTable(): string
    {
        return config('filament-comments.channels_table_name', 'comment_channels');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'channel_id');
    }

    public function members(): BelongsToMany
    {
        if ($this->project_id) {
            $project = $this->project;

            if ($project && method_exists($project, 'members')) {
                return $project->members();
            }
        }

        $userModel = config('filament-comments.user_model') ?? config('auth.providers.users.model', \App\Models\User::class);

        return $this->belongsToMany(
            $userModel,
            config('filament-comments.channel_members_table_name', 'comment_channel_members'),
            'channel_id',
            'user_id'
        )->withTimestamps();
    }

    /**
     * Direct channel members (from pivot table), regardless of project.
     */
    public function channelMembers(): BelongsToMany
    {
        $userModel = config('filament-comments.user_model') ?? config('auth.providers.users.model', \App\Models\User::class);

        return $this->belongsToMany(
            $userModel,
            config('filament-comments.channel_members_table_name', 'comment_channel_members'),
            'channel_id',
            'user_id'
        )->withTimestamps();
    }

    public function createdBy(): BelongsTo
    {
        $userModel = config('filament-comments.user_model') ?? config('auth.providers.users.model', \App\Models\User::class);

        return $this->belongsTo($userModel, 'created_by');
    }

    public function project(): BelongsTo
    {
        $projectModel = config('filament-comments.project_model', \App\Models\Project::class);

        return $this->belongsTo($projectModel, 'project_id');
    }

    /**
     * Scope to only channels (not DMs).
     */
    public function scopeChannels(Builder $query): Builder
    {
        return $query->where('type', ChannelType::Channel);
    }

    /**
     * Scope to only direct messages.
     */
    public function scopeDirectMessages(Builder $query): Builder
    {
        return $query->where('type', ChannelType::DirectMessage);
    }

    public function isDirectMessage(): bool
    {
        return $this->type === ChannelType::DirectMessage;
    }

    public function isChannel(): bool
    {
        return $this->type === ChannelType::Channel || $this->type === null;
    }

    /**
     * Find or create a direct message channel between the given users.
     *
     * Accepts either two scalar IDs (legacy 1-to-1) or an array of user IDs
     * for group DMs. The current authenticated user is always included.
     */
    public static function findOrCreateDirectMessage(int|array $userIds, ?int $userId2 = null): static
    {
        // Normalise to an array that always includes the current user
        if (is_int($userIds)) {
            $allUserIds = [$userIds, $userId2 ?? auth()->id()];
        } else {
            $allUserIds = $userIds;
            if (auth()->check() && ! in_array(auth()->id(), $allUserIds)) {
                $allUserIds[] = auth()->id();
            }
        }

        $allUserIds = collect($allUserIds)->unique()->sort()->values()->all();
        $memberCount = count($allUserIds);

        $userModel = config('filament-comments.user_model') ?? config('auth.providers.users.model', \App\Models\User::class);

        // Find existing DM with the exact same set of members
        $query = static::query()->directMessages()->withCount('channelMembers');

        foreach ($allUserIds as $uid) {
            $query->whereHas('channelMembers', fn (Builder $q) => $q->where('user_id', $uid));
        }

        $existing = $query->get()->first(fn ($ch) => $ch->channel_members_count === $memberCount);

        if ($existing) {
            return $existing;
        }

        // Build display name from all participants
        $labelColumn = config('filament-comments.mentionable.column.label', 'name');
        $users = $userModel::whereIn('id', $allUserIds)->get();
        $name = $users->pluck($labelColumn)->implode(', ');

        // Build a deterministic slug from sorted IDs
        $slug = 'dm-' . implode('-', $allUserIds) . '-' . time();

        $channel = static::create([
            'name' => $name,
            'slug' => $slug,
            'type' => ChannelType::DirectMessage,
            'visibility' => 'private',
            'show_sidebar' => true,
            'created_by' => auth()->id(),
        ]);

        $channel->channelMembers()->syncWithoutDetaching($allUserIds);

        return $channel;
    }

    /**
     * Get the number of unread comments in this channel for the given user.
     */
    public function unreadCount(?int $userId = null): int
    {
        $userId ??= auth()->id();

        if (! $userId) {
            return 0;
        }

        try {
            $readsTable = config('filament-comments.channel_reads_table_name', 'comment_channel_reads');
            $commentsTable = config('filament-comments.table_name', 'comments');

            $lastReadAt = DB::table($readsTable)
                ->where('channel_id', $this->id)
                ->where('user_id', $userId)
                ->value('last_read_at');

            $query = $this->comments()->whereNull('parent_id');

            if ($lastReadAt) {
                $query->where("{$commentsTable}.created_at", '>', $lastReadAt);
            }

            return $query->count();
        } catch (\Throwable) {
            // Table might not exist yet (before migration)
            return 0;
        }
    }

    /**
     * Mark this channel as read for the given user.
     */
    public function markAsRead(?int $userId = null): void
    {
        $userId ??= auth()->id();

        if (! $userId) {
            return;
        }

        try {
            $readsTable = config('filament-comments.channel_reads_table_name', 'comment_channel_reads');

            DB::table($readsTable)->updateOrInsert(
                ['channel_id' => $this->id, 'user_id' => $userId],
                ['last_read_at' => now(), 'updated_at' => now()],
            );
        } catch (\Throwable) {
            // Table might not exist yet (before migration)
        }
    }

    /**
     * Get the display name for a DM from the perspective of the given user.
     * Shows the other participants' names (e.g. "Alice, Bob" or "Alice, Bob +2").
     */
    public function dmDisplayName(?int $currentUserId = null): string
    {
        if (! $this->isDirectMessage()) {
            return $this->name;
        }

        $currentUserId ??= auth()->id();

        if (! $currentUserId) {
            return $this->name;
        }

        $labelColumn = config('filament-comments.mentionable.column.label', 'name');

        // Use eager-loaded relation if available, otherwise query
        $members = $this->relationLoaded('channelMembers')
            ? $this->channelMembers
            : $this->channelMembers()->get();

        $others = $members->filter(fn ($m) => $m->id !== $currentUserId);

        if ($others->isEmpty()) {
            return $this->name;
        }

        // Show up to 3 names, then "+N" for the rest
        $maxNames = 3;
        $names = $others->take($maxNames)->pluck($labelColumn);
        $remaining = $others->count() - $maxNames;

        $display = $names->implode(', ');

        if ($remaining > 0) {
            $display .= ' +' . $remaining;
        }

        return $display;
    }

    /**
     * Get the avatar URL for a DM from the perspective of the given user.
     * Returns the other participant's avatar.
     */
    public function dmAvatarUrl(?int $currentUserId = null): ?string
    {
        if (! $this->isDirectMessage()) {
            return null;
        }

        $currentUserId ??= auth()->id();
        $avatarColumn = config('filament-comments.mentionable.column.avatar', 'profile_photo_path');

        $otherMember = $this->channelMembers()
            ->where('user_id', '!=', $currentUserId)
            ->first();

        return $otherMember?->{$avatarColumn};
    }
}
