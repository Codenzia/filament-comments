<div>
    <div class="space-y-4">
        @forelse($comments as $comment)
            <div class="flex gap-4 rounded-2xl border border-gray-200/70 dark:border-gray-800/80 p-4 bg-white/60 dark:bg-gray-900/40">
                <div class="flex-shrink-0">
                    <img
                        src="{{ optional($comment->commentator)->getFilamentAvatarUrl() }}"
                        class="w-10 h-10 rounded-full border border-gray-200 dark:border-gray-700"
                        alt="{{ optional($comment->commentator)->name }}"
                    />
                </div>
                <div class="flex-1">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                            {{ optional($comment->commentator)->name ?? __('Unknown User') }}
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ $comment->created_at?->diffForHumans() }}
                        </div>
                    </div>

                    <div class="prose prose-sm dark:prose-invert max-w-none text-gray-700 dark:text-gray-200 mt-2">
                        {!! $comment->comment !!}
                    </div>

                    <div class="mt-4 flex gap-2">
                        <x-filament::button
                            color="success"
                            size="sm"
                            wire:click="approveComment({{ $comment->id }})"
                        >
                            {{ __('Approve') }}
                        </x-filament::button>
                        <x-filament::button
                            color="danger"
                            size="sm"
                            wire:click="deleteComment({{ $comment->id }})"
                        >
                            {{ __('Delete') }}
                        </x-filament::button>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-10 text-gray-500">
                {{ __('No comments waiting for approval.') }}
            </div>
        @endforelse
    </div>

</div>
