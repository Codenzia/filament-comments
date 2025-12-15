<div class="flex gap-3">
    <div class="flex-shrink-0">
        @if ($comment->commentator->avatar_url ?? null)
            <img src="{{ $comment->commentator->avatar_url }}" alt="{{ $comment->commentator->name }}" class="h-10 w-10 rounded-full">
        @else
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700">
                <span class="text-sm font-medium text-gray-600 dark:text-gray-300">
                    {{ strtoupper(substr($comment->commentator->name, 0, 2)) }}
                </span>
            </div>
        @endif
    </div>

    <div class="flex-1 space-y-2">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ $comment->commentator->name }}
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $comment->created_at->diffForHumans() }}
                </span>
            </div>

            @if (auth()->id() === $comment->user_id)
                <button
                    wire:click="delete"
                    wire:confirm="{{ __('codenzia-comments::codenzia-comments.comments.delete_confirm') }}"
                    class="text-xs text-gray-400 hover:text-red-600 dark:text-gray-500 dark:hover:text-red-400"
                >
                    <x-filament::icon
                        icon="heroicon-o-trash"
                        class="h-4 w-4"
                    />
                </button>
            @endif
        </div>

        <div class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-300">
            {!! $comment->comment !!}
        </div>

        {{-- Reply Button --}}
        <div class="flex items-center gap-3">
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

            @if ($comment->replies->count() > 0)
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ trans_choice('codenzia-comments::codenzia-comments.comments.replies_count', $comment->replies->count(), ['count' => $comment->replies->count()]) }}
                </span>
            @endif
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
        @if ($comment->replies->count() > 0)
            <div class="mt-4 space-y-4 pl-4 border-l-2 border-gray-200 dark:border-gray-700">
                @foreach ($comment->replies as $reply)
                    <livewire:codenzia-comments::comment-item
                        :key="'reply-' . $reply->id"
                        :comment="$reply"
                    />
                @endforeach
            </div>
        @endif
    </div>
</div>
