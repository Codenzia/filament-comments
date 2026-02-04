<div class="flex flex-col h-full space-y-4">
    @if ($channels->count() > 1)
        <div class="flex items-center space-x-2 border-b border-gray-200 dark:border-gray-700 pb-2">
            @foreach ($channels as $channel)
                <button
                    wire:click="setActiveChannel({{ $channel->id }})"
                    @class([
                        'px-4 py-2 text-sm font-medium rounded-t-lg transition-colors',
                        'bg-primary-50 text-primary-600 border-b-2 border-primary-600 dark:bg-primary-900/20 dark:text-primary-400' => $activeChannelId === $channel->id,
                        'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' => $activeChannelId !== $channel->id,
                    ])
                >
                    {{ $channel->name }}
                </button>
            @endforeach
        </div>
    @endif

    <div class="space-y-4">
        
        {{ $this->form  }}
        <x-filament::button
            wire:click="create"
            color="primary"
        >
            {{ __('codenzia-comments::codenzia-comments.comments.add') }}
        </x-filament::button>
    </div>

    @if ($comments->count())
        <div class="space-y-6">
            @foreach ($comments as $comment)
                <livewire:codenzia-comments::comment-item
                    :key="$comment->id"
                    :comment="$comment"
                    :mentionables="$mentionables"
                />
            @endforeach
        </div>
    @else
        <div class="flex h-full flex-col items-center justify-center space-y-4">
            <x-filament::icon
                icon="heroicon-o-chat-bubble-left-right"
                class="h-12 w-12 text-gray-400 dark:text-gray-500"
            />

            <div class="text-sm text-gray-400 dark:text-gray-500">
                {{ __('codenzia-comments::codenzia-comments.comments.empty') }}
            </div>
        </div>
    @endif

    <x-filament-actions::modals />
</div>
