<div class="flex flex-col h-full"
    x-init="setTimeout(() => window.__scrollCommentsToBottom && window.__scrollCommentsToBottom(false), 300)"
>
    {{-- Header: Watch + Resolved Filter --}}
    <div class="flex items-center justify-between gap-2 px-3 py-1.5">
        <div class="flex items-center gap-2">
            {{-- Resolved filter toggle --}}
            <button
                wire:click="toggleShowResolved"
                @class([
                    'inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-medium transition-colors',
                    'bg-success-500 text-gray-900 dark:bg-success-600 dark:text-gray-900' => $showResolved,
                    'text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5' => ! $showResolved,
                ])
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-3.5 w-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                {{ $showResolved ? __('filament-comments::messages.ui.showing_resolved') : __('filament-comments::messages.ui.show_resolved') }}
            </button>

            {{-- Sort order dropdown --}}
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button
                    type="button"
                    @click="open = !open"
                    class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-medium text-gray-500 transition-colors hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5"
                    :class="open && 'bg-gray-100 dark:bg-white/5'"
                >
                    <x-filament::icon
                        :icon="$sortOrder === 'newest' ? 'heroicon-m-bars-arrow-down' : 'heroicon-m-bars-arrow-up'"
                        class="h-3.5 w-3.5"
                    />
                    {{ $sortOrder === 'newest'
                        ? __('filament-comments::messages.ui.sort_newest_first')
                        : __('filament-comments::messages.ui.sort_oldest_first') }}
                    <x-filament::icon icon="heroicon-m-chevron-down" class="h-3 w-3 transition-transform" ::class="open && 'rotate-180'" />
                </button>

                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute left-0 top-full z-20 mt-1 w-44 rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-white/10 dark:bg-gray-900"
                    x-cloak
                >
                    <button
                        type="button"
                        wire:click="setSortOrder('newest')"
                        @click="open = false"
                        @class([
                            'flex w-full items-center gap-2 px-3 py-1.5 text-xs transition-colors',
                            'text-primary-600 dark:text-primary-400' => $sortOrder === 'newest',
                            'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5' => $sortOrder !== 'newest',
                        ])
                    >
                        <x-filament::icon icon="heroicon-m-bars-arrow-down" class="h-3.5 w-3.5" />
                        {{ __('filament-comments::messages.ui.sort_newest_first') }}
                    </button>
                    <button
                        type="button"
                        wire:click="setSortOrder('oldest')"
                        @click="open = false"
                        @class([
                            'flex w-full items-center gap-2 px-3 py-1.5 text-xs transition-colors',
                            'text-primary-600 dark:text-primary-400' => $sortOrder === 'oldest',
                            'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5' => $sortOrder !== 'oldest',
                        ])
                    >
                        <x-filament::icon icon="heroicon-m-bars-arrow-up" class="h-3.5 w-3.5" />
                        {{ __('filament-comments::messages.ui.sort_oldest_first') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- Watch/Unwatch bell --}}
        <button
            wire:click="toggleWatch"
            @class([
                'inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-medium transition-colors',
                'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-400' => $isWatching,
                'text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/5' => ! $isWatching,
            ])
            title="{{ $isWatching ? __('filament-comments::messages.ui.unwatch') : __('filament-comments::messages.ui.watch_discussion') }}"
        >
            <x-filament::icon :icon="$isWatching ? 'heroicon-s-bell' : 'heroicon-o-bell'" class="h-3.5 w-3.5" />
            {{ $isWatching ? __('filament-comments::messages.ui.watching') : __('filament-comments::messages.ui.watch') }}
        </button>
    </div>

    {{-- Pinned Comment --}}
    @if ($pinnedComment)
        <div class="mx-3 mb-2 rounded-lg border-l-2 border-l-primary-500">
            <div class="rounded-r-lg border border-l-0 border-gray-200 bg-primary-50/50 dark:border-white/10 dark:bg-primary-500/[0.06]">
                <div class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-primary-600 dark:text-primary-400">
                    <x-filament::icon icon="heroicon-s-map-pin" class="h-3.5 w-3.5" />
                    {{ __('filament-comments::messages.ui.pinned') }}
                </div>
                <livewire:filament-comments::comment-item
                    :key="'pinned-' . $pinnedComment->id"
                    :comment="$pinnedComment"
                    :mentionables="$mentionables"
                    :channelMentionables="$channelMentionables"
                />
            </div>
        </div>
    @endif

    {{-- Comments List --}}
    @if ($comments->count())
        <div class="comments-list pb-2">
            @php $lastDate = null; @endphp
            @foreach ($comments as $comment)
                @php $currentDate = $comment->created_at->toDateString(); @endphp
                @if ($currentDate !== $lastDate)
                    <div class="my-4 flex items-center" x-data="{ open: false }" :class="open && 'relative z-30'" data-date-separator="{{ $currentDate }}" id="date-{{ $currentDate }}">
                        <div class="h-px flex-1 bg-gray-300 dark:bg-white/10"></div>
                        <div class="relative shrink-0">
                            <button
                                @click="open = !open"
                                @click.outside="open = false"
                                class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-white px-3 py-0.5 text-xs font-medium text-gray-500 transition-colors hover:bg-gray-50 dark:border-white/10 dark:bg-gray-900 dark:text-gray-400 dark:hover:bg-white/5"
                            >
                                {{ $comment->created_at->format('l, F j') }}
                                <x-filament::icon icon="heroicon-m-chevron-down" class="h-3 w-3 transition-transform" ::class="open && 'rotate-180'" />
                            </button>

                            {{-- Jump-to dropdown --}}
                            <div
                                x-show="open"
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="absolute left-1/2 top-full z-20 mt-1 w-48 -translate-x-1/2 rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-white/10 dark:bg-gray-900"
                                x-cloak
                            >
                            <div class="px-3 py-1.5 text-[11px] font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">
                                {{ __('filament-comments::messages.ui.jump_to') }}
                            </div>
                            <button @click="open = false; window.__commentsJumpTo($el, 'last')" class="flex w-full items-center gap-2 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5">
                                {{ __('filament-comments::messages.ui.most_recent') }}
                            </button>
                            <button @click="open = false; window.__commentsJumpTo($el, 'week')" class="flex w-full items-center gap-2 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5">
                                {{ __('filament-comments::messages.ui.last_week') }}
                            </button>
                            <button @click="open = false; window.__commentsJumpTo($el, 'month')" class="flex w-full items-center gap-2 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5">
                                {{ __('filament-comments::messages.ui.last_month') }}
                            </button>
                            <button @click="open = false; window.__commentsJumpTo($el, 'first')" class="flex w-full items-center gap-2 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5">
                                {{ __('filament-comments::messages.ui.the_very_beginning') }}
                            </button>
                            </div>
                        </div>
                        <div class="h-px flex-1 bg-gray-300 dark:bg-white/10"></div>
                    </div>
                    @php $lastDate = $currentDate; @endphp
                @endif
                <livewire:filament-comments::comment-item
                    :key="$comment->id"
                    :comment="$comment"
                    :mentionables="$mentionables"
                    :channelMentionables="$channelMentionables"
                />
            @endforeach
        </div>
    @else
        <div class="flex flex-1 flex-col items-center justify-center overflow-y-auto py-16">
            <div class="rounded-full bg-gray-100 p-4 dark:bg-white/5">
                <x-filament::icon
                    icon="heroicon-o-chat-bubble-left-right"
                    class="h-8 w-8 text-gray-400 dark:text-gray-500"
                />
            </div>
            <h3 class="mt-4 text-sm font-medium text-gray-900 dark:text-white">
                {{ __('filament-comments::messages.comments.empty_title') ?? 'No comments yet' }}
            </h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ __('filament-comments::messages.comments.empty') }}
            </p>
        </div>
    @endif

    {{-- Comment Form --}}
    @if ($canPost)
        <div
            class="relative mt-auto shrink-0 sticky bottom-0 z-20 pt-3 pb-1"
            x-data="{
                uploading: false,
                dropdownOpen: false,
                hasContent: false,
                _observer: null,
                _bound: false,
                initEditorWatch() {
                    const self = this;
                    const bind = () => {
                        const pm = self.$el.querySelector('.ProseMirror');
                        if (!pm) return false;
                        if (self._bound) return true;
                        self._bound = true;
                        const check = () => {
                            const text = pm.innerText.replace(/\n/g, '').trim();
                            self.hasContent = text.length > 0;
                        };
                        self._observer = new MutationObserver(check);
                        self._observer.observe(pm, { childList: true, subtree: true, characterData: true });
                        pm.addEventListener('input', check);
                        pm.addEventListener('keydown', (e) => {
                            if (e.key === 'Enter' && !e.shiftKey && !e.ctrlKey && !e.altKey) {
                                e.preventDefault();
                                e.stopImmediatePropagation();
                                if (self.hasContent && !self.uploading) {
                                    $wire.create().then(() => setTimeout(() => window.__scrollCommentsToBottom(), 150));
                                }
                            }
                        }, true);
                        check();
                        return true;
                    };
                    // Retry until ProseMirror renders
                    const interval = setInterval(() => { if (bind()) clearInterval(interval); }, 200);
                    setTimeout(() => clearInterval(interval), 5000);
                },
            }"
            x-init="initEditorWatch()"
            x-on:livewire-upload-start="uploading = true"
            x-on:livewire-upload-finish="uploading = false; if ($refs.imageInput) $refs.imageInput.value = ''; if ($refs.fileInput) $refs.fileInput.value = ''"
            x-on:livewire-upload-error="uploading = false; if ($refs.imageInput) $refs.imageInput.value = ''; if ($refs.fileInput) $refs.fileInput.value = ''"
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

            {{-- Hidden file input for document upload --}}
            <input
                type="file"
                wire:model="tempFiles"
                accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.ppt,.pptx"
                multiple
                class="hidden"
                x-ref="fileInput"
            />

            @if ($commentType === 'vote')
                {{-- Vote mode: replaces the text editor entirely --}}
                <div class="comment-composer rounded-xl border border-gray-200 dark:border-white/10">
                    <div class="p-3">
                        <div class="flex items-center justify-between mb-2">
                            <span class="flex items-center gap-1.5 text-xs font-semibold text-gray-600 dark:text-gray-300">
                                <x-filament::icon icon="heroicon-o-chart-bar" class="h-3.5 w-3.5 text-primary-500" />
                                {{ __('filament-comments::messages.comment_types.poll') }}
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
                            class="flex items-center justify-center rounded-lg dark:hover:bg-gray-800 p-1.5"
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
            @elseif ($commentType === 'event')
                {{-- Event mode --}}
                <div class="comment-composer rounded-xl border border-gray-200 dark:border-white/10">
                    <div class="p-3">
                        <div class="flex items-center justify-between mb-2">
                            <span class="flex items-center gap-1.5 text-xs font-semibold text-gray-600 dark:text-gray-300">
                                <x-filament::icon icon="heroicon-o-calendar-days" class="h-3.5 w-3.5 text-primary-500" />
                                {{ __('filament-comments::messages.comment_types.event') }}
                            </span>
                            <button
                                wire:click="setCommentType('text')"
                                class="rounded-md p-0.5 text-gray-400 transition-colors hover:bg-gray-200 hover:text-gray-600 dark:hover:bg-white/10 dark:hover:text-gray-300"
                            >
                                <x-filament::icon icon="heroicon-o-x-mark" class="h-3.5 w-3.5" />
                            </button>
                        </div>
                        {{ $this->eventForm }}
                    </div>
                    {{-- Bottom toolbar for event --}}
                    <div class="flex items-center border-t border-gray-700 dark:border-gray-700 px-2 py-1.5">
                        <div class="flex-1"></div>
                        <button
                            wire:click="create"
                            wire:loading.attr="disabled"
                            class="flex items-center justify-center rounded-lg dark:hover:bg-gray-800 p-1.5"
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
            @elseif ($commentType === 'meeting')
                {{-- Meeting mode --}}
                <div class="comment-composer rounded-xl border border-gray-200 dark:border-white/10">
                    <div class="p-3">
                        <div class="flex items-center justify-between mb-2">
                            <span class="flex items-center gap-1.5 text-xs font-semibold text-gray-600 dark:text-gray-300">
                                <x-filament::icon icon="heroicon-o-video-camera" class="h-3.5 w-3.5 text-primary-500" />
                                {{ __('filament-comments::messages.comment_types.meeting') }}
                            </span>
                            <button
                                wire:click="setCommentType('text')"
                                class="rounded-md p-0.5 text-gray-400 transition-colors hover:bg-gray-200 hover:text-gray-600 dark:hover:bg-white/10 dark:hover:text-gray-300"
                            >
                                <x-filament::icon icon="heroicon-o-x-mark" class="h-3.5 w-3.5" />
                            </button>
                        </div>
                        {{ $this->meetingForm }}
                    </div>
                    <div class="flex items-center border-t border-gray-700 dark:border-gray-700 px-2 py-1.5">
                        <div class="flex-1"></div>
                        <button
                            wire:click="create"
                            wire:loading.attr="disabled"
                            class="flex items-center justify-center rounded-lg dark:hover:bg-gray-800 p-1.5"
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
            @elseif ($commentType === 'todo')
                {{-- Todo / Checklist mode --}}
                <div class="comment-composer rounded-xl border border-gray-200 dark:border-white/10">
                    <div class="p-3">
                        <div class="flex items-center justify-between mb-2">
                            <span class="flex items-center gap-1.5 text-xs font-semibold text-gray-600 dark:text-gray-300">
                                <x-filament::icon icon="heroicon-o-clipboard-document-check" class="h-3.5 w-3.5 text-primary-500" />
                                {{ __('filament-comments::messages.comment_types.todo') }}
                            </span>
                            <button
                                wire:click="setCommentType('text')"
                                class="rounded-md p-0.5 text-gray-400 transition-colors hover:bg-gray-200 hover:text-gray-600 dark:hover:bg-white/10 dark:hover:text-gray-300"
                            >
                                <x-filament::icon icon="heroicon-o-x-mark" class="h-3.5 w-3.5" />
                            </button>
                        </div>
                        {{ $this->todoForm }}
                    </div>
                    <div class="flex items-center border-t border-gray-700 dark:border-gray-700 px-2 py-1.5">
                        <div class="flex-1"></div>
                        <button
                            wire:click="create"
                            wire:loading.attr="disabled"
                            class="flex items-center justify-center rounded-lg dark:hover:bg-gray-800 p-1.5"
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
            @elseif ($commentType === 'survey')
                {{-- Survey mode --}}
                <div class="comment-composer rounded-xl border border-gray-200 dark:border-white/10">
                    <div class="p-3">
                        <div class="flex items-center justify-between mb-2">
                            <span class="flex items-center gap-1.5 text-xs font-semibold text-gray-600 dark:text-gray-300">
                                <x-filament::icon icon="heroicon-o-clipboard-document-list" class="h-3.5 w-3.5 text-primary-500" />
                                {{ __('filament-comments::messages.comment_types.survey') }}
                            </span>
                            <button
                                wire:click="setCommentType('text')"
                                class="rounded-md p-0.5 text-gray-400 transition-colors hover:bg-gray-200 hover:text-gray-600 dark:hover:bg-white/10 dark:hover:text-gray-300"
                            >
                                <x-filament::icon icon="heroicon-o-x-mark" class="h-3.5 w-3.5" />
                            </button>
                        </div>
                        {{ $this->surveyForm }}
                    </div>
                    <div class="flex items-center border-t border-gray-700 dark:border-gray-700 px-2 py-1.5">
                        <div class="flex-1"></div>
                        <button
                            wire:click="create"
                            wire:loading.attr="disabled"
                            class="flex items-center justify-center rounded-lg dark:hover:bg-gray-800 p-1.5"
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
            @elseif ($commentType === 'risk')
                {{-- Risk mode --}}
                <div class="comment-composer rounded-xl border border-gray-200 dark:border-white/10">
                    <div class="p-3">
                        <div class="flex items-center justify-between mb-2">
                            <span class="flex items-center gap-1.5 text-xs font-semibold text-gray-600 dark:text-gray-300">
                                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-3.5 w-3.5 text-danger-500" />
                                {{ __('filament-comments::messages.comment_types.risk') }}
                            </span>
                            <button
                                wire:click="setCommentType('text')"
                                class="rounded-md p-0.5 text-gray-400 transition-colors hover:bg-gray-200 hover:text-gray-600 dark:hover:bg-white/10 dark:hover:text-gray-300"
                            >
                                <x-filament::icon icon="heroicon-o-x-mark" class="h-3.5 w-3.5" />
                            </button>
                        </div>
                        {{ $this->riskForm }}
                    </div>
                    <div class="flex items-center border-t border-gray-700 dark:border-gray-700 px-2 py-1.5">
                        <div class="flex-1"></div>
                        <button
                            wire:click="create"
                            wire:loading.attr="disabled"
                            class="flex items-center justify-center rounded-lg dark:hover:bg-gray-800 p-1.5"
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
                <div class="comment-composer relative rounded-xl border border-gray-200 dark:border-white/10">
                    {{-- Settings cog (top-right) --}}
                    @if (config('filament-comments.composer.show_settings', false))
                        <div class="absolute top-1.5 right-2 z-10" x-data="{ settingsOpen: false }">
                            <button
                                @click="settingsOpen = !settingsOpen"
                                class="flex items-center justify-center rounded-md p-1 text-gray-400 transition-colors hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                                title="{{ __('Settings') }}"
                            >
                                <x-filament::icon icon="heroicon-o-cog-6-tooth" class="h-3.5 w-3.5" />
                            </button>
                            <div
                                x-show="settingsOpen"
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                @click.away="settingsOpen = false"
                                class="absolute right-0 z-50 mt-1 w-56 rounded-lg bg-white p-3 shadow-lg ring-1 ring-gray-200/80 dark:bg-gray-900 dark:ring-gray-700"
                            >
                                <label class="block text-[11px] font-medium text-gray-500 dark:text-gray-400 mb-2">
                                    Composer Background
                                </label>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ([
                                        '#16181C' => 'Default',
                                        '#1a1d21' => 'Slate',
                                        '#1e1e2e' => 'Mocha',
                                        '#0d1117' => 'GitHub',
                                        '#2b2d30' => 'JetBrains',
                                        '#1f2937' => 'Gray 800',
                                        '#172554' => 'Blue',
                                        '#1a2e05' => 'Green',
                                        '#2d1b4e' => 'Purple',
                                        '#3b0a0a' => 'Wine',
                                    ] as $color => $label)
                                        <button
                                            type="button"
                                            @click="document.querySelectorAll('.comment-composer').forEach(el => el.style.backgroundColor = '{{ $color }}'); localStorage.setItem('fc-composer-bg', '{{ $color }}')"
                                            class="group/swatch relative h-6 w-6 rounded-full ring-1 ring-white/20 transition-transform hover:scale-110"
                                            style="background-color: {{ $color }}"
                                            title="{{ $label }}"
                                        ></button>
                                    @endforeach
                                </div>
                                <button
                                    type="button"
                                    @click="document.querySelectorAll('.comment-composer').forEach(el => el.style.backgroundColor = ''); localStorage.removeItem('fc-composer-bg')"
                                    class="mt-2 w-full rounded-md px-2 py-1 text-[10px] font-medium text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/5 dark:hover:text-gray-300"
                                >
                                    Reset to default
                                </button>
                            </div>
                        </div>
                    @endif

                    <div class="comment-composer__editor">
                        {{ $this->form }}
                    </div>

                    {{-- Bottom toolbar --}}
                    <div class="relative flex items-center gap-0.5 px-2 py-1.5">
                        <div class="relative flex items-center gap-0.5">
                            {{-- + Add attachment --}}
                            <button
                                @click="dropdownOpen = !dropdownOpen"
                                class="flex items-center justify-center rounded-md p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300"
                                title="{{ __('filament-comments::messages.comment_types.add_attachment') }}"
                            >
                                <x-filament::icon icon="heroicon-o-plus" class="h-4.5 w-4.5" />
                            </button>

                            {{-- Dropdown menu --}}
                            <div
                                x-show="dropdownOpen"
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                @click.away="dropdownOpen = false"
                                class="absolute left-0 z-50 mb-2 bottom-full w-48 rounded-lg bg-white py-1 shadow-lg ring-1 ring-gray-200/80 dark:bg-gray-900 dark:ring-gray-700"
                            >
                                <button
                                    wire:click="setCommentType('vote')"
                                    @click="dropdownOpen = false"
                                    class="flex w-full items-center gap-3 px-3 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5"
                                >
                                    <x-filament::icon icon="heroicon-o-chart-bar" class="h-4 w-4 text-gray-400 dark:text-gray-500" />
                                    {{ __('filament-comments::messages.comment_types.poll') }}
                                </button>
                                <button
                                    wire:click="setCommentType('event')"
                                    @click="dropdownOpen = false"
                                    class="flex w-full items-center gap-3 px-3 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5"
                                >
                                    <x-filament::icon icon="heroicon-o-calendar-days" class="h-4 w-4 text-gray-400 dark:text-gray-500" />
                                    {{ __('filament-comments::messages.comment_types.event') }}
                                </button>
                                <button
                                    wire:click="setCommentType('meeting')"
                                    @click="dropdownOpen = false"
                                    class="flex w-full items-center gap-3 px-3 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5"
                                >
                                    <x-filament::icon icon="heroicon-o-video-camera" class="h-4 w-4 text-gray-400 dark:text-gray-500" />
                                    {{ __('filament-comments::messages.comment_types.meeting') }}
                                </button>
                                <button
                                    wire:click="setCommentType('todo')"
                                    @click="dropdownOpen = false"
                                    class="flex w-full items-center gap-3 px-3 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5"
                                >
                                    <x-filament::icon icon="heroicon-o-clipboard-document-check" class="h-4 w-4 text-gray-400 dark:text-gray-500" />
                                    {{ __('filament-comments::messages.comment_types.todo') }}
                                </button>
                                <button
                                    wire:click="setCommentType('survey')"
                                    @click="dropdownOpen = false"
                                    class="flex w-full items-center gap-3 px-3 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5"
                                >
                                    <x-filament::icon icon="heroicon-o-clipboard-document-list" class="h-4 w-4 text-gray-400 dark:text-gray-500" />
                                    {{ __('filament-comments::messages.comment_types.survey') }}
                                </button>
                                <button
                                    wire:click="setCommentType('risk')"
                                    @click="dropdownOpen = false"
                                    class="flex w-full items-center gap-3 px-3 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5"
                                >
                                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-4 w-4 text-gray-400 dark:text-gray-500" />
                                    {{ __('filament-comments::messages.comment_types.risk') }}
                                </button>
                            </div>

                            <span class="mx-0.5 h-4 w-px bg-gray-200 dark:bg-gray-700"></span>

                            {{-- @ mention --}}
                            <button
                                class="flex items-center justify-center rounded-md p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300"
                                title="{{ __('filament-comments::messages.comments.mention_hint') }}"
                                onclick="window.__triggerMention(this.closest('.comment-composer'))"
                            >
                                <x-filament::icon icon="heroicon-o-at-symbol" class="h-4.5 w-4.5" />
                            </button>

                            {{-- Image shortcut --}}
                            <button
                                @click="$refs.imageInput.click()"
                                class="flex items-center justify-center rounded-md p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300"
                                title="{{ __('filament-comments::messages.comment_types.image') }}"
                            >
                                <x-filament::icon icon="heroicon-o-photo" class="h-4.5 w-4.5" />
                            </button>

                            {{-- File upload --}}
                            <button
                                @click="$refs.fileInput.click()"
                                class="flex items-center justify-center rounded-md p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300"
                                title="{{ __('filament-comments::messages.comment_types.file') ?? 'Attach file' }}"
                            >
                                <x-filament::icon icon="heroicon-o-paper-clip" class="h-4.5 w-4.5" />
                            </button>

                            {{-- Emoji picker --}}
                            <div class="relative" x-data="{ emojiOpen: false }">
                                <button
                                    @click="emojiOpen = !emojiOpen"
                                    class="flex items-center justify-center rounded-md p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300"
                                    title="{{ __('filament-comments::messages.comments.emoji') ?? 'Emoji' }}"
                                >
                                    <x-filament::icon icon="heroicon-o-face-smile" class="h-4.5 w-4.5" />
                                </button>
                                <div
                                    x-show="emojiOpen"
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95"
                                    @click.away="emojiOpen = false"
                                    class="absolute left-0 z-50 mb-2 bottom-full w-64 max-h-48 overflow-y-auto rounded-lg bg-white p-2 shadow-lg ring-1 ring-gray-200/80 dark:bg-gray-900 dark:ring-gray-700"
                                >
                                    <div class="flex flex-wrap">
                                        @foreach (['😀','😂','😊','😍','🥰','😎','🤔','😏','😢','😭','😡','🤯','🥳','😴','🤗','😈','👍','👎','👏','🙌','🤝','✌️','🔥','❤️','💯','⭐','🎉','✅','❌','💡','🚀','👀','💬','📌','🏆','💪'] as $emoji)
                                            <button
                                                type="button"
                                                @click="window.__insertEmoji($el.closest('.comment-composer'), '{{ $emoji }}'); emojiOpen = false"
                                                class="flex items-center justify-center rounded p-1 text-lg dark:bg-gray-900"
                                            >
                                                {{ $emoji }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex-1"></div>

                        {{-- Upload indicator --}}
                        <div x-show="uploading" x-cloak class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500">
                            <x-filament::loading-indicator class="h-3.5 w-3.5" />
                            {{ __('uploading') }}
                        </div>

                        {{-- Send button --}}
                        <button
                            @click="$wire.create().then(() => setTimeout(() => window.__scrollCommentsToBottom(), 150))"
                            wire:loading.attr="disabled"
                            :disabled="uploading || !hasContent"
                            :class="hasContent && !uploading
                                ? 'bg-primary-500 text-white hover:bg-primary-600'
                                : 'text-gray-300 dark:text-gray-600 cursor-not-allowed'"
                            class="flex items-center justify-center rounded-lg p-1.5 transition-colors duration-150"
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
        <div class="mt-6 flex items-center justify-between gap-2 rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-600 ring-1 ring-gray-200 dark:bg-white/5 dark:text-gray-400 dark:ring-white/10">
            <div class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-lock-closed" class="h-4 w-4 shrink-0" />
                {{ __('Only members of this channel can post comments.') }}
            </div>
            <x-filament::button
                wire:click="joinChannel"
                color="primary"
                size="xs"
            >
                {{ __('Join now') }}
            </x-filament::button>
        </div>
    @endif

    <x-filament-actions::modals />

    @assets
    @if (config('filament-comments.code_highlighting', true))
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css" class="hljs-light-theme">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css" class="hljs-dark-theme">
        <style>
            /* Force correct hljs backgrounds — toggleAttribute('disabled') is unreliable */
            .dark pre code.hljs,
            .dark .comment-body pre code {
                background: #0d1117 !important;
                color: #c9d1d9 !important;
            }
            :root:not(.dark) pre code.hljs,
            :root:not(.dark) .comment-body pre code {
                background: #f6f8fa !important;
            }
        </style>
        <script>
            (function() {
                function toggleHljsTheme() {
                    const isDark = document.documentElement.classList.contains('dark');
                    document.querySelector('.hljs-light-theme')?.toggleAttribute('disabled', isDark);
                    document.querySelector('.hljs-dark-theme')?.toggleAttribute('disabled', !isDark);
                }
                toggleHljsTheme();
                new MutationObserver(toggleHljsTheme).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
            })();
        </script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
        <script>
            document.addEventListener('livewire:navigated', function() {
                document.querySelectorAll('.comment-body pre code').forEach(function(el) {
                    hljs.highlightElement(el);
                });
            });
            // Also run after Livewire updates
            if (typeof Livewire !== 'undefined') {
                document.addEventListener('livewire:update', function() {
                    setTimeout(function() {
                        document.querySelectorAll('.comment-body pre code:not(.hljs)').forEach(function(el) {
                            hljs.highlightElement(el);
                        });
                    }, 200);
                });
            }
        </script>
    @endif
    <style>
        .comment-composer {
            background-color: {{ config('filament-comments.composer.bg', '#ffffff') }};
        }
        .dark .comment-composer {
            background-color: {{ config('filament-comments.composer.dark_bg', '#16181C') }};
        }
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
        .comment-body img {
            width: 20%;
        }
        /* Code block styling */
        .comment-code-block {
            border-radius: 0.5rem;
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }
        .dark .comment-code-block {
            border-color: rgba(255, 255, 255, 0.1);
        }
        .code-copy-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            padding: 6px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            opacity: 0;
            transition: opacity 150ms;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.07);
            color: rgba(0, 0, 0, 0.4);
        }
        .code-copy-btn:hover {
            color: rgba(0, 0, 0, 0.8);
        }
        .code-copy-btn.copied {
            color: #34d399 !important;
        }
        .comment-code-block:hover .code-copy-btn {
            opacity: 1;
        }
        .dark .code-copy-btn {
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.5);
        }
        .dark .code-copy-btn:hover {
            color: rgba(255, 255, 255, 0.9);
        }
    </style>
    <script>
        // Restore composer background from localStorage
        (function() {
            var saved = localStorage.getItem('fc-composer-bg');
            if (saved) {
                document.addEventListener('DOMContentLoaded', function() {
                    document.querySelectorAll('.comment-composer').forEach(function(el) {
                        el.style.backgroundColor = saved;
                    });
                });
                // Also run immediately for Livewire navigations
                document.querySelectorAll('.comment-composer').forEach(function(el) {
                    el.style.backgroundColor = saved;
                });
            }
        })();

        window.__scrollCommentsToBottom = function(smooth) {
            var commentsList = document.querySelector('.comments-list');
            if (commentsList) {
                // Walk up to find the nearest scrollable ancestor
                var scrollable = commentsList.parentElement;
                while (scrollable && scrollable !== document.body && scrollable !== document.documentElement) {
                    var style = window.getComputedStyle(scrollable);
                    if ((style.overflowY === 'auto' || style.overflowY === 'scroll') && scrollable.scrollHeight > scrollable.clientHeight) {
                        scrollable.scrollTo({ top: scrollable.scrollHeight, behavior: smooth !== false ? 'smooth' : 'instant' });
                        return;
                    }
                    scrollable = scrollable.parentElement;
                }
            }
            // Fallback: scroll the window (standalone discussion page)
            window.scrollTo({ top: document.body.scrollHeight, behavior: smooth !== false ? 'smooth' : 'instant' });
        };

        window.__commentsJumpTo = function(el, target) {
            var list = el.closest('.comments-list');
            if (!list) return;
            // Find the scrollable ancestor
            var scrollable = list.parentElement;
            while (scrollable && scrollable !== document.body && scrollable !== document.documentElement) {
                var style = window.getComputedStyle(scrollable);
                if ((style.overflowY === 'auto' || style.overflowY === 'scroll') && scrollable.scrollHeight > scrollable.clientHeight) {
                    break;
                }
                scrollable = scrollable.parentElement;
            }
            if (!scrollable || scrollable === document.body || scrollable === document.documentElement) {
                scrollable = null; // will use window
            }

            var dest = null;
            if (target === 'first') {
                dest = list.firstElementChild;
            } else if (target === 'last') {
                dest = list.lastElementChild;
            } else {
                var seps = Array.from(list.querySelectorAll('[data-date-separator]'));
                var now = new Date();
                var cutoff;
                if (target === 'week') {
                    cutoff = new Date(now - 7 * 86400000);
                } else if (target === 'month') {
                    cutoff = new Date(now.getFullYear(), now.getMonth() - 1, now.getDate());
                }
                // Find the separator closest to cutoff (last one <= cutoff)
                var reversed = seps.slice().reverse();
                dest = reversed.find(function(s) { return new Date(s.dataset.dateSeparator) <= cutoff; }) || seps[0];
            }

            if (dest) {
                if (scrollable) {
                    var offset = dest.offsetTop - list.offsetTop;
                    scrollable.scrollTo({ top: offset, behavior: 'smooth' });
                } else {
                    dest.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        };

        window.__getComposerEditor = function(rootEl) {
            if (!rootEl) return null;
            var pm = rootEl.querySelector('.ProseMirror[contenteditable="true"]');
            if (!pm) return null;
            try {
                var data = Alpine.$data(pm);
                if (data && typeof data.getEditor === 'function') {
                    return data.getEditor();
                }
            } catch(e) {}
            return null;
        };

        window.__insertEmoji = function(composerEl, emoji) {
            var editor = window.__getComposerEditor(composerEl);
            if (!editor) return;
            editor.chain().focus().insertContent(emoji).run();
        };

        window.__triggerMention = function(composerEl) {
            var editor = window.__getComposerEditor(composerEl);
            var pm = composerEl.querySelector('.ProseMirror[contenteditable="true"]');
            if (!pm) return;

            // Step 1: Focus the editor to establish a cursor position
            if (editor) {
                editor.commands.focus('end');
            } else {
                pm.focus();
            }

            // Step 2: Wait for focus + selection to fully settle in the browser
            requestAnimationFrame(function() {
                setTimeout(function() {
                    var keyOpts = { key: '@', code: 'Digit2', keyCode: 50, which: 50, bubbles: true, cancelable: true };

                    // Simulate full keystroke: keydown → native insert → keyup
                    pm.dispatchEvent(new KeyboardEvent('keydown', keyOpts));
                    document.execCommand('insertText', false, '@');
                    pm.dispatchEvent(new KeyboardEvent('keyup', keyOpts));
                }, 120);
            });
        };

        window.__insertCommentImages = function(urls) {
            setTimeout(function() {
                var composerEl = document.querySelector('.comment-composer');
                var editor = window.__getComposerEditor(composerEl);
                if (!editor || !urls || !urls.length) return;

                var html = '';
                urls.forEach(function(u) {
                    html += '<img src="' + u + '" alt="" style="max-width:100%;border-radius:8px;margin:4px 0;"><br>';
                });
                editor.chain().focus().insertContent(html).run();
            }, 150);
        };

        window.__insertReplyImages = function(commentId, urls) {
            setTimeout(function() {
                var composerEl = document.querySelector('.reply-composer-' + commentId);
                var editor = window.__getComposerEditor(composerEl);
                if (!editor || !urls || !urls.length) return;

                var html = '';
                urls.forEach(function(u) {
                    html += '<img src="' + u + '" alt="" style="max-width:100%;border-radius:8px;margin:4px 0;"><br>';
                });
                editor.chain().focus().insertContent(html).run();
            }, 150);
        };

        window.__getFileIcon = function(ext) {
            var icons = {
                pdf: '📄', doc: '📝', docx: '📝', xls: '📊', xlsx: '📊',
                csv: '📊', ppt: '📽️', pptx: '📽️', txt: '📃'
            };
            return icons[ext] || '📎';
        };

        window.__buildFileHtml = function(files) {
            var html = '';
            files.forEach(function(f) {
                var icon = window.__getFileIcon(f.extension);
                var ext = f.extension.toUpperCase();
                html += '<a href="' + f.url + '" target="_blank" rel="noopener" '
                    + 'style="display:inline-flex;align-items:center;gap:8px;padding:8px 12px;margin:4px 0;'
                    + 'border-radius:8px;border:1px solid rgba(128,128,128,0.25);background:rgba(128,128,128,0.06);'
                    + 'text-decoration:none;color:inherit;font-size:13px;max-width:100%;">'
                    + '<span style="font-size:20px;line-height:1;">' + icon + '</span>'
                    + '<span style="min-width:0;overflow:hidden;">'
                    + '<span style="display:block;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">'
                    + f.name + '</span>'
                    + '<span style="display:block;font-size:11px;opacity:0.6;">' + ext + ' file</span>'
                    + '</span></a><br>';
            });
            return html;
        };

        window.__insertCommentFiles = function(files) {
            setTimeout(function() {
                var composerEl = document.querySelector('.comment-composer');
                var editor = window.__getComposerEditor(composerEl);
                if (!editor || !files || !files.length) return;
                editor.chain().focus().insertContent(window.__buildFileHtml(files)).run();
            }, 150);
        };

        window.__insertReplyFiles = function(commentId, files) {
            setTimeout(function() {
                var composerEl = document.querySelector('.reply-composer-' + commentId);
                var editor = window.__getComposerEditor(composerEl);
                if (!editor || !files || !files.length) return;
                editor.chain().focus().insertContent(window.__buildFileHtml(files)).run();
            }, 150);
        };
    </script>
    @endassets
</div>
