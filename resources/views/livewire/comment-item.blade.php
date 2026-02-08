<div class="flex gap-3 group comment-item">
    <div class="flex-shrink-0">
        @php
            $avatarColumn = config('codenzia-comments.mentionable.column.avatar', 'avatar_url');
            $avatarPath = $comment->commentator->{$avatarColumn} ?? null;
        @endphp
        @if ($avatarPath)
            <img src="{{ asset('storage/' . $avatarPath) }}" alt="{{ $comment->commentator->name }}" class="h-10 w-10 rounded-full">
        @else
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700">
                <span class="text-sm font-medium text-gray-600 dark:text-gray-300">
                    {{ strtoupper(substr($comment->commentator->name, 0, 2)) }}
                </span>
            </div>
        @endif
    </div>

    <div class="flex-1 space-y-2">
        <div class="flex items-center justify-start gap-5">
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ $comment->commentator->name }}
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $comment->created_at->diffForHumans() }}
                </span>
            </div>

            @if (auth()->id() === $comment->user_id)
                <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200 comment-actions">
                    <button
                        wire:click="edit"
                        class="text-xs text-gray-400 hover:text-blue-600 dark:text-gray-500 dark:hover:text-blue-400"
                        title="{{ __('codenzia-comments::codenzia-comments.comments.edit') }}"
                    >
                        <x-filament::icon
                            icon="heroicon-o-pencil"
                            class="h-4 w-4"
                        />
                    </button>
                    <button
                        wire:click="delete"
                        wire:confirm="{{ __('codenzia-comments::codenzia-comments.comments.delete_confirm') }}"
                        class="text-xs text-gray-400 hover:text-red-600 dark:text-gray-500 dark:hover:text-red-400"
                        title="{{ __('codenzia-comments::codenzia-comments.comments.delete') }}"
                    >
                        <x-filament::icon
                            icon="heroicon-o-trash"
                            class="h-4 w-4"
                        />
                    </button>
                </div>
            @endif
        </div>

        @if ($showEditForm)
            {{-- Edit Form --}}
            <div class="space-y-2">
                {{ $this->editForm }}
                <div class="flex gap-2">
                    <x-filament::button
                        wire:click="updateComment"
                        size="sm"
                        color="primary"
                    >
                        {{ __('codenzia-comments::codenzia-comments.comments.save') }}
                    </x-filament::button>
                    <x-filament::button
                        wire:click="toggleEditForm"
                        size="sm"
                        color="gray"
                        outlined
                    >
                        {{ __('codenzia-comments::codenzia-comments.comments.cancel') }}
                    </x-filament::button>
                </div>
            </div>
        @else
            <div class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300">
                {!! $comment->comment !!}
            </div>
        @endif

        {{-- Reactions --}}
        @php
            $reactions = $comment->getReactionsSummary();
            $userReaction = $comment->userReaction();
            $reactionTypes = config('codenzia-comments.reactions', []);
            $hasAnyReactions = collect($reactions)->sum() > 0;
        @endphp
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-2 flex-wrap relative">
                {{-- Reaction Picker Button --}}
                <div class="relative" x-data="{ open: @entangle('showReactionPicker') }">
                    <button
                        wire:click="toggleReactionPicker"
                        class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs transition-colors bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700"
                        title="Add reaction"
                    >
                        <x-filament::icon
                            icon="heroicon-o-face-smile"
                            class="h-4 w-4"
                        />
                    </button>

                    {{-- Reaction Picker Popover --}}
                    <div
                        x-show="open"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        @click.away="open = false"
                        class="absolute bottom-full left-0 mb-2 z-50 flex items-center gap-1 px-2 py-1.5 bg-white dark:bg-gray-800 rounded-full shadow-lg ring-1 ring-gray-200 dark:ring-gray-700"
                    >
                        @foreach ($reactionTypes as $type => $emoji)
                            <button
                                wire:click="toggleReaction('{{ $type }}')"
                                class="text-xl hover:scale-125 transition-transform duration-150 p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700"
                                title="{{ __('codenzia-comments::codenzia-comments.reactions.' . $type) }}"
                            >
                                {{ $emoji }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Display Active Reactions with Counts --}}
                @if ($hasAnyReactions)
                    <div class="flex items-center gap-1 flex-wrap">
                        @foreach ($reactionTypes as $type => $emoji)
                            @php
                                $count = $reactions[$type] ?? 0;
                                if ($count <= 0) {
                                    continue;
                                }
                                $isActive = $userReaction && $userReaction->reaction_type === $type;
                            @endphp
                            <button
                                wire:click="toggleReaction('{{ $type }}')"
                                class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs transition-colors {{ $isActive ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 ring-1 ring-primary-500' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700' }}"
                                title="{{ __('codenzia-comments::codenzia-comments.reactions.' . $type) }}"
                            >
                                <span class="text-lg">{{ $emoji }}</span>
                                <span class="font-medium">{{ $count }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Reply Button --}}
            <div class="flex items-center gap-3">
                @if ($canReply)
                    <button
                        wire:click="toggleReplyForm"
                        class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 font-medium"
                    >
                        <x-filament::icon
                            icon="heroicon-o-chat-bubble-left"
                            class="h-3 w-3 inline-block mr-1"
                        />
                        {{ __('codenzia-comments::codenzia-comments.comments.reply') }}
                    </button>
                @endif

                @if ($comment->replies->count() > 0)
                    <button
                        wire:click="toggleReplies"
                        class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 font-medium"
                    >
                        @if ($showReplies)
                            <x-filament::icon
                                icon="heroicon-o-chevron-up"
                                class="h-3 w-3 inline-block mr-1"
                            />
                        @else
                            <x-filament::icon
                                icon="heroicon-o-chevron-down"
                                class="h-3 w-3 inline-block mr-1"
                            />
                        @endif
                        {{ trans_choice('codenzia-comments::codenzia-comments.comments.replies_count', $comment->replies->count(), ['count' => $comment->replies->count()]) }}
                    </button>
                @endif
            </div>
        </div>
        {{-- Reply Form --}}
        @if ($showReplyForm)
            <div class="mt-3 space-y-2 pl-4 border-l-2 border-gray-200 dark:border-gray-700">
                {{ $this->replyForm }}
                <div class="flex gap-2">
                    <x-filament::button
                        wire:click="reply"
                        size="sm"
                        color="primary"
                    >
                        {{ __('codenzia-comments::codenzia-comments.comments.post_reply') }}
                    </x-filament::button>
                    <x-filament::button
                        wire:click="toggleReplyForm"
                        size="sm"
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
            <div class="mt-4 space-y-4 pl-4 border-l-2 border-gray-200 dark:border-gray-700">
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
