<div class="comment-item group relative flex gap-3 p-3 ">
    {{-- Avatar --}}
    <div class="flex-shrink-0 pt-0.5">
        @php
            $avatarColumn = config('codenzia-comments.mentionable.column.avatar', 'avatar_url');
            $avatarPath = $comment->commentator->{$avatarColumn} ?? null;
        @endphp
        @if ($avatarPath)
            <img
                src="{{ asset('storage/' . $avatarPath) }}"
                alt="{{ $comment->commentator->name }}"
                class="h-9 w-9 rounded-full object-cover ring-2 ring-white dark:ring-gray-800 shadow-sm"
            >
        @else
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-50 ring-2 ring-white dark:ring-gray-800 shadow-sm dark:bg-primary-500/10">
                <span class="text-xs font-semibold text-primary-600 dark:text-primary-400">
                    {{ strtoupper(substr($comment->commentator->name, 0, 2)) }}
                </span>
            </div>
        @endif
    </div>

    {{-- Content --}}
    <div class="min-w-0 flex-1">
        {{-- Header: Name · Timestamp · Actions --}}
        <div class="flex items-center gap-2">
            <span class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                {{ $comment->commentator->name }}
            </span>
            <span class="shrink-0 text-[11px] text-gray-400 dark:text-gray-500" title="{{ $comment->created_at->format('M d, Y \a\t h:i A') }}">
                {{ $comment->created_at->diffForHumans() }}
            </span>

            @if ($comment->created_at->ne($comment->updated_at))
                <span class="shrink-0 text-[10px] italic text-gray-400 dark:text-gray-500">
                    {{ __('codenzia-comments::codenzia-comments.comments.edited') ?? 'edited' }}
                </span>
            @endif

            {{-- Actions (owner only) --}}
            @if (auth()->id() === $comment->user_id)
                <div class="mr-auto flex items-center gap-1 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                    <button
                        wire:click="edit"
                        class="rounded-md p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/5 dark:hover:text-gray-300"
                        title="{{ __('codenzia-comments::codenzia-comments.comments.edit') }}"
                    >
                        <x-filament::icon icon="heroicon-o-pencil-square" class="h-3.5 w-3.5" />
                    </button>
                    <button
                        wire:click="delete"
                        wire:confirm="{{ __('codenzia-comments::codenzia-comments.comments.delete_confirm') }}"
                        class="rounded-md p-1 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-500 dark:text-gray-500 dark:hover:bg-red-500/10 dark:hover:text-red-400"
                        title="{{ __('codenzia-comments::codenzia-comments.comments.delete') }}"
                    >
                        <x-filament::icon icon="heroicon-o-trash" class="h-3.5 w-3.5" />
                    </button>
                </div>
            @endif
        </div>

        {{-- Body --}}
        @if ($showEditForm)
            <div class="mt-2 space-y-2">
                {{ $this->editForm }}
                <div class="flex items-center gap-2">
                    <x-filament::button
                        wire:click="updateComment"
                        size="xs"
                        color="primary"
                    >
                        <span wire:loading.remove wire:target="updateComment">{{ __('codenzia-comments::codenzia-comments.comments.save') }}</span>
                        <span wire:loading wire:target="updateComment">
                            <x-filament::loading-indicator class="h-4 w-4" />
                        </span>
                    </x-filament::button>
                    <x-filament::button
                        wire:click="toggleEditForm"
                        size="xs"
                        color="gray"
                        outlined
                    >
                        {{ __('codenzia-comments::codenzia-comments.comments.cancel') }}
                    </x-filament::button>
                </div>
            </div>
        @else
            <div
                class="comment-body prose prose-sm mt-1 max-w-none text-gray-700 dark:prose-invert dark:text-gray-300"
                x-data="mentionPopover(@js($mentionables))"
                x-on:mouseover.capture="showPopover($event)"
                x-on:mouseout.capture="hidePopover($event)"
            >
                {!! $comment->comment !!}

                {{-- Mention Hover Popover --}}
                <div
                    x-ref="popover"
                    x-show="visible"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    x-cloak
                    class="mention-popover fixed z-[9999] w-64 rounded-xl bg-white shadow-xl ring-1 ring-gray-200/80 dark:bg-gray-800 dark:ring-gray-700"
                    style="pointer-events: auto;"
                    x-on:mouseenter="clearTimeout(_timeout)"
                    x-on:mouseleave="hidePopover($event)"
                >
                    <a :href="user ? user.link : '#'" class="block p-4 no-underline transition-colors hover:bg-gray-50 rounded-xl dark:hover:bg-white/5">
                        <div class="flex items-center gap-3">
                            <template x-if="user && user.avatar">
                                <img :src="user.avatar" :alt="user.key" class="h-10 w-10 rounded-full object-cover ring-2 ring-white shadow-sm dark:ring-gray-700">
                            </template>
                            <template x-if="user && !user.avatar">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-50 ring-2 ring-white shadow-sm dark:bg-primary-500/10 dark:ring-gray-700">
                                    <span class="text-sm font-semibold text-primary-600 dark:text-primary-400" x-text="user ? user.key.substring(0, 2).toUpperCase() : ''"></span>
                                </div>
                            </template>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-gray-900 dark:text-white" x-text="user ? user.key : ''"></p>
                                <template x-if="user && user.email">
                                    <p class="truncate text-xs text-gray-500 dark:text-gray-400" x-text="user.email"></p>
                                </template>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        @endif

        {{-- Footer: Reactions + Reply + Replies Toggle --}}
        @php
            $reactions = $comment->getReactionsSummary();
            $userReaction = $comment->userReaction();
            $reactionTypes = config('codenzia-comments.reactions', []);
            $hasAnyReactions = collect($reactions)->sum() > 0;
        @endphp

        @if ($canPost)
        <div class="mt-2 flex flex-wrap items-center gap-2">
            {{-- Reaction Picker --}}
            <div class="relative" x-data="{ open: @entangle('showReactionPicker') }">
                <button
                    wire:click="toggleReactionPicker"
                    class="inline-flex items-center justify-center rounded-full p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/5 dark:hover:text-gray-300"
                    title="Add reaction"
                >
                    <x-filament::icon icon="heroicon-o-face-smile" class="h-4 w-4" />
                </button>

                {{-- Picker Popover --}}
                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    @click.away="open = false"
                    class="absolute bottom-full left-0 z-50 mb-2 flex items-center gap-0.5 rounded-full bg-white px-2 py-1.5 shadow-lg ring-1 ring-gray-200/80 dark:bg-gray-800 dark:ring-gray-700"
                >
                    @foreach ($reactionTypes as $type => $emoji)
                        <button
                            wire:click="toggleReaction('{{ $type }}')"
                            class="rounded-full p-1 text-lg transition-transform duration-100 hover:scale-125 hover:bg-gray-100 dark:hover:bg-gray-700"
                            title="{{ __('codenzia-comments::codenzia-comments.reactions.' . $type) }}"
                        >
                            {{ $emoji }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Active Reaction Badges --}}
            @if ($hasAnyReactions)
                @foreach ($reactionTypes as $type => $emoji)
                    @php
                        $count = $reactions[$type] ?? 0;
                        if ($count <= 0) continue;
                        $isActive = $userReaction && $userReaction->reaction_type === $type;
                    @endphp
                    <button
                        wire:click="toggleReaction('{{ $type }}')"
                        @class([
                            'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium transition-all duration-150',
                            'bg-primary-50 text-primary-700 ring-1 ring-primary-200 dark:bg-primary-500/10 dark:text-primary-300 dark:ring-primary-500/30' => $isActive,
                            'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-white/5 dark:text-gray-400 dark:hover:bg-white/10' => ! $isActive,
                        ])
                        title="{{ __('codenzia-comments::codenzia-comments.reactions.' . $type) }}"
                    >
                        <span class="text-sm leading-none">{{ $emoji }}</span>
                        <span>{{ $count }}</span>
                    </button>
                @endforeach
            @endif

            {{-- Divider --}}
            @if ($hasAnyReactions && ($canPost || $comment->replies->count() > 0))
                <span class="mx-0.5 h-3.5 w-px bg-gray-200 dark:bg-gray-700"></span>
            @endif

            {{-- Reply Button --}}
            @if ($canPost)
                <button
                    wire:click="toggleReplyForm"
                    class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-xs font-medium text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300"
                >
                    <x-filament::icon icon="heroicon-o-chat-bubble-left" class="h-3.5 w-3.5" />
                    {{ __('codenzia-comments::codenzia-comments.comments.reply') }}
                </button>
            @endif

            {{-- Replies Toggle --}}
            @if ($comment->replies->count() > 0)
                <button
                    wire:click="toggleReplies"
                    class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-xs font-medium text-primary-600 transition-colors hover:bg-primary-50 dark:text-primary-400 dark:hover:bg-primary-500/10"
                >
                    <x-filament::icon
                        :icon="$showReplies ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down'"
                        class="h-3.5 w-3.5"
                    />
                    {{ trans_choice('codenzia-comments::codenzia-comments.comments.replies_count', $comment->replies->count(), ['count' => $comment->replies->count()]) }}
                </button>
            @endif
        </div>
        @endif

        {{-- Reply Form --}}
        @if ($showReplyForm)
            <div class="mt-3 border-l-2 border-primary-200 pl-4 dark:border-primary-500/30">
                {{ $this->replyForm }}
                <div class="mt-2 flex items-center gap-2">
                    <x-filament::button
                        wire:click="reply"
                        size="xs"
                        color="primary"
                    >
                        <span wire:loading.remove wire:target="reply">{{ __('codenzia-comments::codenzia-comments.comments.post_reply') }}</span>
                        <span wire:loading wire:target="reply">
                            <x-filament::loading-indicator class="h-4 w-4" />
                        </span>
                    </x-filament::button>
                    <x-filament::button
                        wire:click="toggleReplyForm"
                        size="xs"
                        color="gray"
                        outlined
                    >
                        {{ __('codenzia-comments::codenzia-comments.comments.cancel') }}
                    </x-filament::button>
                </div>
            </div>
        @endif

        {{-- Nested Replies --}}
        @if ($showReplies && $comment->replies->count() > 0)
            <div class="mt-3 space-y-1 border-l-2 border-gray-200 pl-3 dark:border-gray-700/60">
                @foreach ($comment->replies as $reply)
                    <livewire:codenzia-comments::comment-item
                        :key="'reply-' . $reply->id"
                        :comment="$reply"
                        :mentionables="$mentionables"
                        :channelMentionables="$channelMentionables"
                    />
                @endforeach
            </div>
        @endif
    </div>

    <x-filament-actions::modals />
</div>

@script
<script>
if (typeof window.__mentionPopoverRegistered === 'undefined') {
    window.__mentionPopoverRegistered = true;
    Alpine.data('mentionPopover', (mentionables) => ({
        visible: false,
        user: null,
        _timeout: null,

        showPopover(event) {
            const link = event.target.closest('a.tribute-mention');
            if (!link) return;

            clearTimeout(this._timeout);

            const mentionText = (link.textContent || '').replace(/^@/, '').trim();

            this.user = (mentionables || []).find(
                u => u.key && u.key.toLowerCase() === mentionText.toLowerCase()
            ) || null;

            if (!this.user) return;

            const rect = link.getBoundingClientRect();
            const popover = this.$refs.popover;
            if (popover) {
                popover.style.left = rect.left + 'px';
                popover.style.top = (rect.bottom + 6) + 'px';
            }

            this.visible = true;
        },

        hidePopover(event) {
            this._timeout = setTimeout(() => {
                this.visible = false;
                this.user = null;
            }, 200);
        },
    }));
}
</script>
@endscript
