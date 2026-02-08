<div class="flex flex-col h-full space-y-4">
    @if ($canPost)
        <div class="space-y-4 mb-8">
            {{ $this->form  }}
            <x-filament::button
                wire:click="create"
                color="primary"
            >
                {{ __('codenzia-comments::codenzia-comments.comments.add') }}
            </x-filament::button>
        </div>
    @else
        <div class="p-4 mb-4 text-sm text-yellow-800 rounded-lg bg-yellow-50 dark:bg-gray-800 dark:text-yellow-300" role="alert">
            {{ __('Only members of this channel can post comments.') }}
        </div>
    @endif

    @if ($comments->count())
        <div class="space-y-6">
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
