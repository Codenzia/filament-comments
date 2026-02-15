<div class="flex flex-col h-full">
    {{-- Comment Form --}}
    @if ($canPost)
        <div class="relative mb-6">
            {{-- Vote / Image attachment panel (shown above composer when active) --}}
            @if ($commentType === 'vote')
                <div class="mb-3 rounded-lg p-3">
                    <div class="flex items-center justify-between mb-2">
                        <span class="flex items-center gap-1.5 text-xs font-semibold text-gray-600 dark:text-gray-300">
                            <x-filament::icon icon="heroicon-o-chart-bar" class="h-3.5 w-3.5 text-primary-500" />
                            {{ __('codenzia-comments::codenzia-comments.comment_types.vote') }}
                        </span>
                        <button
                            wire:click="setCommentType('text')"
                            class="rounded-md p-0.5 text-gray-400 transition-colors"
                        >
                            <x-filament::icon icon="heroicon-o-x-mark" class="h-3.5 w-3.5" />
                        </button>
                    </div>
                    {{ $this->voteForm }}
                </div>
            @elseif ($commentType === 'image')
                <div class="mb-3 rounded-lg p-3">
                    <div class="flex items-center justify-between mb-2">
                        <span class="flex items-center gap-1.5 text-xs font-semibold text-gray-600 dark:text-gray-300">
                            <x-filament::icon icon="heroicon-o-photo" class="h-3.5 w-3.5 text-primary-500" />
                            {{ __('codenzia-comments::codenzia-comments.comment_types.image') }}
                        </span>
                        <button
                            wire:click="setCommentType('text')"
                            class="rounded-md p-0.5 text-gray-400 transition-colors"
                        >
                            <x-filament::icon icon="heroicon-o-x-mark" class="h-3.5 w-3.5" />
                        </button>
                    </div>
                    {{ $this->imageForm }}
                </div>
            @endif

            {{-- Unified Slack-style composer box --}}
            <div class="comment-composer overflow-hidden rounded-xl dark:bg-[#16181C]">
                {{-- Rich editor area (toolbar + text) --}}
                <div class="comment-composer__editor">
                    {{ $this->form }}
                </div>

                {{-- Bottom toolbar --}}
                <div class="flex items-center gap-0.5 border-t border-gray-700 dark:border-gray-700 px-2 py-1.5">
                    {{-- Left icons --}}
                    <div class="relative flex items-center gap-0.5" x-data="{ open: false }">
                        {{-- + Add attachment --}}
                        <button
                            @click="open = !open"
                            @class([
                                'flex items-center justify-center rounded-md p-1.5 transition-colors',
                                'text-gray-400 hover:bg-[#212427] hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300',
                            ])
                            title="{{ __('codenzia-comments::codenzia-comments.comment_types.add_attachment') }}"
                        >
                            <x-filament::icon icon="heroicon-o-plus" class="h-4.5 w-4.5" />
                        </button>

                        {{-- Dropdown menu --}}
                        <div
                            x-show="open"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            @click.away="open = false"
                            class="absolute left-0 z-50 mb-8 bottom-0 w-48 rounded-lg bg-white py-1 ring-1 ring-gray-200/80 dark:bg-[#16181C] dark:ring-gray-700"
                        >
                            <button
                                wire:click="setCommentType('image')"
                                @click="open = false"
                                class="flex w-full items-center px-3 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5 gap-3"
                            >
                                <x-filament::icon icon="heroicon-o-photo" class="h-4 w-4 text-gray-400 dark:text-gray-500" />
                                {{ __('codenzia-comments::codenzia-comments.comment_types.image') }}
                            </button>
                            <button
                                wire:click="setCommentType('vote')"
                                @click="open = false"
                                class="flex w-full items-center gap-3 px-3 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5"
                            >
                                <x-filament::icon icon="heroicon-o-chart-bar" class="h-4 w-4 text-gray-400 dark:text-gray-500" />
                                {{ __('codenzia-comments::codenzia-comments.comment_types.vote') }}
                            </button>
                        </div>

                        {{-- @ mention hint --}}
                        <button
                            class="flex items-center justify-center rounded-md p-1.5 text-gray-400 transition-colors hover:bg-[#212427] hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300"
                            title="{{ __('codenzia-comments::codenzia-comments.comments.mention_hint') }}"
                            onclick="this.closest('.comment-composer').querySelector('.ProseMirror')?.focus(); document.execCommand('insertText', false, '@')"
                        >
                            <x-filament::icon icon="heroicon-o-at-symbol" class="h-4.5 w-4.5" />
                        </button>
                    </div>

                    {{-- Spacer --}}
                    <div class="flex-1"></div>

                    {{-- Send button (right) --}}
                    <button
                        wire:click="create"
                        wire:loading.attr="disabled"
                        class="flex items-center justify-center rounded-lg dark:hover:bg-[#212427] p-1.5"
                    >
                        <span wire:loading.remove wire:target="create">
                            <x-filament::icon icon="heroicon-o-paper-airplane" class="h-4 w-4" />
                        </span>
                        <span wire:loading wire:target="create">
                            <x-filament::loading-indicator class="h-4 w-4" />
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <style>
            /* Strip the default Filament rich editor chrome so it merges into the composer box */
            .comment-composer__editor .fi-fo-rich-editor {
                border: none !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                background: transparent !important;
                --tw-ring-shadow: none !important;
                ring: none !important;
            }
            .comment-composer__editor .fi-fo-rich-editor .fi-fo-rich-editor-toolbar {
                border-radius: 0 !important;
                border-left: none !important;
                border-right: none !important;
                border-top: none !important;
                background: transparent !important;
            }
            .comment-composer__editor .fi-fo-rich-editor .tiptap-wrapper,
            .comment-composer__editor .fi-fo-rich-editor .tiptap-editor,
            .comment-composer__editor .fi-fo-rich-editor .ProseMirror {
                border: none !important;
                box-shadow: none !important;
                background: transparent !important;
            }
            .comment-composer__editor .fi-fo-rich-editor .ProseMirror {
                min-height: 48px !important;
                max-height: 200px;
                overflow-y: auto;
                padding: 8px 12px !important;
            }
            /* Remove the outer wrapper ring/border from Filament form component */
            .comment-composer__editor .fi-fo-field-wrp {
                --tw-ring-shadow: none !important;
            }
            .comment-composer__editor .fi-fo-rich-editor-content {
                border: none !important;
                border-radius: 0 !important;
            }
        </style>
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
            <div class="rounded-full bg-[#212427] p-4 dark:bg-white/5">
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
