<div class="flex flex-col h-full">
    {{-- Comment Form --}}
    @if ($canPost)
        <div
            class="relative mb-6"
            x-data="{ uploading: false, dropdownOpen: false }"
            x-on:livewire-upload-start="uploading = true"
            x-on:livewire-upload-finish="uploading = false; $refs.imageInput.value = ''"
            x-on:livewire-upload-error="uploading = false; $refs.imageInput.value = ''"
        >
            {{-- Hidden file input for image upload --}}
            <input
                type="file"
                wire:model="tempImages"
                accept="image/*"
                multiple
                class="hidden"
                x-ref="imageInput"
            />

            @if ($commentType === 'vote')
                {{-- Vote mode: replaces the text editor entirely --}}
                <div class="comment-composer overflow-hidden rounded-xl dark:bg-[#16181C]">
                    <div class="p-3">
                        <div class="flex items-center justify-between mb-2">
                            <span class="flex items-center gap-1.5 text-xs font-semibold text-gray-600 dark:text-gray-300">
                                <x-filament::icon icon="heroicon-o-chart-bar" class="h-3.5 w-3.5 text-primary-500" />
                                {{ __('codenzia-comments::codenzia-comments.comment_types.vote') }}
                            </span>
                            <button
                                wire:click="setCommentType('text')"
                                class="rounded-md p-0.5 text-gray-400 transition-colors hover:bg-gray-200 hover:text-gray-600 dark:hover:bg-white/10 dark:hover:text-gray-300"
                            >
                                <x-filament::icon icon="heroicon-o-x-mark" class="h-3.5 w-3.5" />
                            </button>
                        </div>
                        {{ $this->voteForm }}
                    </div>
                    {{-- Bottom toolbar for vote --}}
                    <div class="flex items-center border-t border-gray-700 dark:border-gray-700 px-2 py-1.5">
                        <div class="flex-1"></div>
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
            @else
                {{-- Text / Image mode: show the rich text editor --}}
                <div class="comment-composer overflow-hidden rounded-xl dark:bg-[#16181C]">
                    <div class="comment-composer__editor">
                        {{ $this->form }}
                    </div>

                    {{-- Bottom toolbar --}}
                    <div class="flex items-center gap-0.5 border-t border-gray-700 dark:border-gray-700 px-2 py-1.5">
                        <div class="relative flex items-center gap-0.5">
                            {{-- + Add attachment --}}
                            <button
                                @click="dropdownOpen = !dropdownOpen"
                                class="flex items-center justify-center rounded-md p-1.5 text-gray-400 transition-colors hover:bg-[#212427] hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300"
                                title="{{ __('codenzia-comments::codenzia-comments.comment_types.add_attachment') }}"
                            >
                                <x-filament::icon icon="heroicon-o-plus" class="h-4.5 w-4.5" />
                            </button>

                            {{-- Dropdown menu --}}
                            <div
                                x-show="dropdownOpen"
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                @click.away="dropdownOpen = false"
                                class="absolute left-0 z-50 mb-8 bottom-0 w-48 rounded-lg bg-white py-1 ring-1 ring-gray-200/80 dark:bg-[#16181C] dark:ring-gray-700"
                            >
                                <button
                                    @click="dropdownOpen = false; $refs.imageInput.click()"
                                    class="flex w-full items-center gap-3 px-3 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5"
                                >
                                    <x-filament::icon icon="heroicon-o-photo" class="h-4 w-4 text-gray-400 dark:text-gray-500" />
                                    {{ __('codenzia-comments::codenzia-comments.comment_types.image') }}
                                </button>
                                <button
                                    wire:click="setCommentType('vote')"
                                    @click="dropdownOpen = false"
                                    class="flex w-full items-center gap-3 px-3 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5"
                                >
                                    <x-filament::icon icon="heroicon-o-chart-bar" class="h-4 w-4 text-gray-400 dark:text-gray-500" />
                                    {{ __('codenzia-comments::codenzia-comments.comment_types.vote') }}
                                </button>
                            </div>

                            <span class="mx-0.5 h-4 w-px bg-gray-200 dark:bg-gray-700"></span>

                            {{-- @ mention --}}
                            <button
                                class="flex items-center justify-center rounded-md p-1.5 text-gray-400 transition-colors hover:bg-[#212427] hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300"
                                title="{{ __('codenzia-comments::codenzia-comments.comments.mention_hint') }}"
                                onclick="this.closest('.comment-composer').querySelector('.ProseMirror')?.focus(); document.execCommand('insertText', false, '@')"
                            >
                                <x-filament::icon icon="heroicon-o-at-symbol" class="h-4.5 w-4.5" />
                            </button>

                            {{-- Image shortcut --}}
                            <button
                                @click="$refs.imageInput.click()"
                                class="flex items-center justify-center rounded-md p-1.5 text-gray-400 transition-colors hover:bg-[#212427] hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300"
                                title="{{ __('codenzia-comments::codenzia-comments.comment_types.image') }}"
                            >
                                <x-filament::icon icon="heroicon-o-photo" class="h-4.5 w-4.5" />
                            </button>
                        </div>

                        <div class="flex-1"></div>

                        {{-- Upload indicator --}}
                        <div x-show="uploading" x-cloak class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500">
                            <x-filament::loading-indicator class="h-3.5 w-3.5" />
                            {{ __('codenzia-comments::codenzia-comments.comment_types.uploading') }}
                        </div>

                        {{-- Send button --}}
                        <button
                            wire:click="create"
                            wire:loading.attr="disabled"
                            :disabled="uploading"
                            class="flex items-center justify-center rounded-lg dark:hover:bg-[#212427] p-1.5 disabled:opacity-50"
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
            @endif
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

    @assets
    <style>
        .comment-composer__editor .fi-fo-rich-editor {
            border: none !important;
            box-shadow: none !important;
            border-radius: 0 !important;
            background: transparent !important;
            --tw-ring-shadow: none !important;
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
        .comment-composer__editor .fi-fo-field-wrp {
            --tw-ring-shadow: none !important;
        }
        .comment-composer__editor .fi-fo-rich-editor-content {
            border: none !important;
            border-radius: 0 !important;
        }
    </style>
    @endassets

    @script
    <script>
        $wire.on('comment-images-uploaded', ({ urls }) => {
            const editor = document.querySelector('.comment-composer .ProseMirror[contenteditable="true"]');
            if (!editor || !urls || !urls.length) return;

            editor.focus();

            urls.forEach(url => {
                const img = '<img src="' + url + '" alt="" style="max-width:100%;border-radius:8px;margin:4px 0;" /><br>';
                document.execCommand('insertHTML', false, img);
            });

            // Sync the DOM change with ProseMirror / Livewire state
            editor.dispatchEvent(new Event('input', { bubbles: true }));
            editor.dispatchEvent(new Event('change', { bubbles: true }));
        });
    </script>
    @endscript
</div>
