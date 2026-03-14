{{--
    Quick Comments — Lightweight comment preview + composer.

    Designed for modals, sidebars, and cards. Uses Alpine.js optimistic UI
    so new comments appear instantly without a full Livewire re-render.
--}}
@php
    $currentUserAvatarJs = $currentUserAvatar ? "'" . e($currentUserAvatar) . "'" : 'null';
@endphp

<div x-data="{
    quickComment: '',
    sending: false,
    newComments: [],
    serverCount: {{ $commentsCount }},
    get totalCount() { return this.serverCount + this.newComments.length },
    submitComment() {
        if (!this.quickComment.trim() || this.sending) return;
        const text = this.quickComment.trim();
        this.sending = true;
        this.newComments.push({
            text: text,
            name: '{{ e($currentUser?->name ?? __('You')) }}',
            avatar: {!! $currentUserAvatarJs !!},
            initial: '{{ e($currentUserInitial) }}',
            time: '{{ __('just now') }}',
        });
        this.quickComment = '';
        $wire.postQuickComment(text)
            .then(() => { this.sending = false; })
            .catch(() => {
                this.sending = false;
                this.newComments.pop();
            });
    }
}" @class([
    'rounded-xl p-5',
    'bg-gray-100 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700' => !$transparent,
])>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
            <x-heroicon-m-chat-bubble-left-right class="w-4 h-4 text-gray-400" />
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                <span
                    x-text="totalCount + ' ' + (totalCount === 1 ? '{{ __('Comment') }}' : '{{ __('Comments') }}')"></span>
            </h3>
        </div>
        @if ($viewAllUrl)
            <a href="{{ $viewAllUrl }}"
                class="text-xs text-primary-500 hover:text-primary-600 dark:hover:text-primary-400 font-medium">
                {{ __('View all') }} &rarr;
            </a>
        @endif
    </div>

    {{-- Recent comments (server-rendered) --}}
    <div class="space-y-4" :class="{ 'mb-5': totalCount > 0 }">
        @if ($commentsCount > 0)
            @foreach ($comments as $comment)
                @php
                    $author = $comment->commentator;
                    $authorAvatarUrl =
                        $author && method_exists($author, 'getFilamentAvatarUrl')
                            ? $author->getFilamentAvatarUrl()
                            : null;

                    if (!$authorAvatarUrl && $author) {
                        $avatarColumn = config('filament-comments.mentionable.column.avatar', 'avatar_url');
                        $avatarPath = $author->{$avatarColumn} ?? null;
                        if ($avatarPath) {
                            $authorAvatarUrl = filter_var($avatarPath, FILTER_VALIDATE_URL)
                                ? $avatarPath
                                : asset('storage/' . $avatarPath);
                        }
                    }

                    $authorInitial = mb_substr($author?->name ?? '?', 0, 1);
                @endphp
                <div class="flex gap-3 items-start">
                    <div class="shrink-0 mt-0.5">
                        @if ($authorAvatarUrl)
                            <img src="{{ $authorAvatarUrl }}" alt="{{ $author?->name }}"
                                class="w-7 h-7 rounded-full object-cover border border-gray-200 dark:border-gray-700">
                        @else
                            <div
                                class="w-7 h-7 rounded-full bg-gray-200 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 flex items-center justify-center">
                                <span class="text-[10px] font-bold text-gray-400">{{ $authorInitial }}</span>
                            </div>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-baseline gap-2">
                            <span
                                class="text-[13px] font-semibold text-gray-800 dark:text-gray-200">{{ $author?->name ?? __('Someone') }}</span>
                            <span
                                class="text-[11px] text-gray-400 dark:text-gray-500">{{ $comment->created_at->diffForHumans(short: true) }}</span>
                        </div>
                        <p class="text-[13px] text-gray-600 dark:text-gray-400 line-clamp-2 mt-0.5 leading-relaxed">
                            {{ \Illuminate\Support\Str::limit(strip_tags($comment->comment), 140) }}
                        </p>
                    </div>
                </div>
            @endforeach
        @endif

        {{-- Optimistically rendered new comments --}}
        <template x-for="(c, i) in newComments" :key="i">
            <div class="flex gap-3 animate-fade-in">
                <template x-if="c.avatar">
                    <img :src="c.avatar" alt=""
                        class="w-7 h-7 rounded-full object-cover shrink-0 mt-0.5 border border-gray-200 dark:border-gray-700">
                </template>
                <template x-if="!c.avatar">
                    <div
                        class="w-7 h-7 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center shrink-0 mt-0.5">
                        <span class="text-[10px] font-bold text-primary-600 dark:text-primary-400"
                            x-text="c.initial"></span>
                    </div>
                </template>
                <div class="min-w-0 flex-1">
                    <div class="flex items-baseline gap-2">
                        <span class="text-[13px] font-semibold text-gray-800 dark:text-gray-200" x-text="c.name"></span>
                        <span class="text-[11px] text-gray-400 dark:text-gray-500" x-text="c.time"></span>
                    </div>
                    <p class="text-[13px] text-gray-600 dark:text-gray-400 line-clamp-2 mt-0.5 leading-relaxed"
                        x-text="c.text"></p>
                </div>
            </div>
        </template>

        <p x-show="totalCount === 0" class="text-sm text-gray-400 dark:text-gray-500 italic">
            {{ __('No comments yet — be the first!') }}
        </p>
    </div>

    {{-- Quick reply composer --}}
    <div class="pt-3">
        <div
            class="flex gap-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-white/10 p-2 focus-within:border-primary-500 focus-within:ring-1 focus-within:ring-primary-500 transition-colors">
            <textarea x-model="quickComment" rows="1"
                class="flex-1 min-h-[36px] max-h-24 resize-none border-0 bg-transparent px-2 py-1.5 text-sm text-gray-800 dark:text-gray-300 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-0 focus:outline-none"
                placeholder="{{ __('Add your comments...') }}" @keydown.enter.prevent="submitComment()"></textarea>
            <button @click="submitComment()" :disabled="!quickComment.trim() || sending"
                class="self-end shrink-0 rounded-md bg-primary-500 hover:bg-primary-600 disabled:opacity-40 disabled:cursor-not-allowed p-2 text-white transition-colors">
                <svg x-show="!sending" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                </svg>
                <svg x-show="sending" x-cloak class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg"
                    fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </button>
        </div>
        <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1">
            {{ __('Press Enter to send') }}
        </p>
    </div>
</div>
