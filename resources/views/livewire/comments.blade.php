<div class="flex flex-col h-full">
    {{-- Comment Form --}}
    @if ($canPost)
        <div class="relative mb-6">
            {{ $this->form }}
            <div class="mt-4 flex items-center justify-between">
                <p class="text-xs text-gray-400 dark:text-gray-500">
                    {{ 'Type @ to mention someone' }}
                </p>
                <x-filament::button
                    wire:click="create"
                    size="sm"
                    color="primary"
                    icon="heroicon-o-paper-airplane"
                    icon-position="after"
                >
                    <span wire:loading.remove wire:target="create">{{ __('codenzia-comments::codenzia-comments.comments.add') }}</span>
                    <span wire:loading wire:target="create">
                        <x-filament::loading-indicator class="h-4 w-4" />
                    </span>
                </x-filament::button>
            </div>
        </div>
    @else
        <div class="mb-6 flex items-center justify-between gap-2 rounded-lg bg-yellow-50 px-4 py-3 text-sm text-yellow-700 ring-1 ring-yellow-200/60 dark:bg-yellow-500/5 dark:text-yellow-400 dark:ring-yellow-500/20">
            <div class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-lock-closed" class="h-4 w-4 shrink-0" />
                {{ __('Only members of this channel can post comments.') }}
            </div>
            <x-filament::button
                wire:click="joinChannel"
                color="warning"
                size="xs"
                variant="outline"
            >
                {{ __('Join now') }}
            </x-filament::button>
        </div>
    @endif

    {{-- Comments List --}}
    @if ($comments->count())
        <div class="comments-list -mx-3">
            @foreach ($comments as $comment)
                <livewire:codenzia-comments::comment-item
                    :key="$comment->id"
                    :comment="$comment"
                    :mentionables="$mentionables"
                    :channelMentionables="$channelMentionables"
                />
            @endforeach
        </div>
    @else
        <div class="flex flex-1 flex-col items-center justify-center py-16">
            <div class="rounded-full bg-gray-100 p-4 dark:bg-white/5">
                <x-filament::icon
                    icon="heroicon-o-chat-bubble-left-right"
                    class="h-8 w-8 text-gray-400 dark:text-gray-500"
                />
            </div>
            <h3 class="mt-4 text-sm font-medium text-gray-900 dark:text-white">
                {{ __('codenzia-comments::codenzia-comments.comments.empty_title') ?? 'No comments yet' }}
            </h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ __('codenzia-comments::codenzia-comments.comments.empty') }}
            </p>
        </div>
    @endif

    <x-filament-actions::modals />
</div>
