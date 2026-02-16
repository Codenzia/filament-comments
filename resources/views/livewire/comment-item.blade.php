<div class="comment-item group relative flex gap-3 p-3 bg-white dark:bg-[#16181C] rounded-lg mb-4">
    {{-- Avatar --}}
    <div class="flex-shrink-0 pt-0.5">
        @php
            $avatarColumn = config('codenzia-comments.mentionable.column.avatar', 'avatar_url');
            $avatarPath = $comment->commentator->{$avatarColumn} ?? null;
        @endphp
        @if ($avatarPath)
            <img
                src="{{ asset('storage/' . $avatarPath) }}"
                alt="{{ $comment->commentator->name }}"
                class="h-9 w-9 rounded-full object-cover ring-2 ring-white dark:ring-gray-800 shadow-sm"
            >
        @else
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-50 ring-2 ring-white dark:ring-gray-800 shadow-sm dark:bg-primary-500/10">
                <span class="text-xs font-semibold text-primary-600 dark:text-primary-400">
                    {{ strtoupper(substr($comment->commentator->name, 0, 2)) }}
                </span>
            </div>
        @endif
    </div>

    {{-- Content --}}
    <div class="min-w-0 flex-1">
        {{-- Header: Name · Timestamp · Actions --}}
        <div class="flex items-center gap-2">
            <span class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                {{ $comment->commentator->name }}
            </span>
            <span class="shrink-0 text-[11px] text-gray-400 dark:text-gray-500" title="{{ $comment->created_at->format('M d, Y \a\t h:i A') }}">
                {{ $comment->created_at->diffForHumans() }}
            </span>

            @if ($comment->created_at->ne($comment->updated_at))
                <span class="shrink-0 text-[10px] italic text-gray-400 dark:text-gray-500">
                    {{ __('codenzia-comments::codenzia-comments.comments.edited') ?? 'edited' }}
                </span>
            @endif

            {{-- Actions (owner only) --}}
            @if (auth()->id() === $comment->user_id)
                <div class="mr-auto flex items-center gap-1 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                    @if ($comment->type === null || $comment->type === \Codenzia\FilamentComments\Enums\CommentType::Text)
                        <button
                            wire:click="edit"
                            class="rounded-md p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/5 dark:hover:text-gray-300"
                            title="{{ __('codenzia-comments::codenzia-comments.comments.edit') }}"
                        >
                            <x-filament::icon icon="heroicon-o-pencil-square" class="h-3.5 w-3.5" />
                        </button>
                    @endif
                    <button
                        wire:click="delete"
                        wire:confirm="{{ __('codenzia-comments::codenzia-comments.comments.delete_confirm') }}"
                        class="rounded-md p-1 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-500 dark:text-gray-500 dark:hover:bg-red-500/10 dark:hover:text-red-400"
                        title="{{ __('codenzia-comments::codenzia-comments.comments.delete') }}"
                    >
                        <x-filament::icon icon="heroicon-o-trash" class="h-3.5 w-3.5" />
                    </button>
                </div>
            @endif
        </div>

        {{-- Body --}}
        @if ($showEditForm)
            <div class="mt-2 space-y-2">
                {{ $this->editForm }}
                <div class="flex items-center gap-2">
                    <x-filament::button
                        wire:click="updateComment"
                        size="xs"
                        color="primary"
                    >
                        <span wire:loading.remove wire:target="updateComment">{{ __('codenzia-comments::codenzia-comments.comments.save') }}</span>
                        <span wire:loading wire:target="updateComment">
                            <x-filament::loading-indicator class="h-4 w-4" />
                        </span>
                    </x-filament::button>
                    <x-filament::button
                        wire:click="toggleEditForm"
                        size="xs"
                        color="gray"
                        outlined
                    >
                        {{ __('codenzia-comments::codenzia-comments.comments.cancel') }}
                    </x-filament::button>
                </div>
            </div>
        @elseif ($comment->type === \Codenzia\FilamentComments\Enums\CommentType::Vote)
            {{-- Vote Rendering --}}
            @php
                $voteData = $comment->getDecodedComment();
                $question = $voteData['question'] ?? '';
                $options = $voteData['options'] ?? [];
                $votes = $voteData['votes'] ?? [];
                $totalVotes = count($votes);
                $userVote = $votes[auth()->id()] ?? null;
            @endphp
            <div class="mt-3 space-y-3">
                {{-- Question header --}}
                <div class="flex items-start gap-2.5">
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-primary-500/10">
                        <x-filament::icon icon="heroicon-o-chart-bar" class="h-4 w-4 text-primary-500" />
                    </div>
                    <p class="text-sm font-semibold leading-7 text-gray-900 dark:text-white">{{ $question }}</p>
                </div>

                {{-- Options --}}
                <div class="space-y-2">
                    @php $maxVotes = collect($votes)->countBy()->max() ?? 0; @endphp
                    @foreach ($options as $index => $option)
                        @php
                            $optionVotes = collect($votes)->filter(fn($v) => $v === $index)->count();
                            $percentage = $totalVotes > 0 ? round(($optionVotes / $totalVotes) * 100) : 0;
                            $isSelected = $userVote === $index;
                            $isWinning = $totalVotes > 0 && $optionVotes === $maxVotes && $optionVotes > 0;
                        @endphp
                        <button
                            wire:click="$parent.castVote({{ $comment->id }}, {{ $index }})"
                            class="group/vote relative w-full overflow-hidden rounded-xl text-left transition-all duration-200 hover:scale-[1.01] active:scale-[0.99]"
                        >
                            {{-- Card background --}}
                            <div @class([
                                'relative rounded-xl border px-4 py-3 transition-all duration-200',
                                'bg-black' => $isSelected,
                                'bg-[#16181C]' => ! $isSelected,
                            ])>
                                {{-- Animated progress bar --}}
                                @if ($totalVotes > 0)
                                    <div
                                        class="absolute inset-y-0 left-0 rounded-xl transition-all duration-500 ease-out"
                                        style="width: {{ $percentage }}%"
                                    >
                                        <div @class([
                                            'h-full w-full rounded-xl',
                                            'bg-gradient-to-r from-primary-500/15 to-primary-400/10' => $isSelected,
                                            'bg-gradient-to-r from-gray-100 to-gray-50 dark:from-white/[0.04] dark:to-white/[0.02]' => ! $isSelected && ! $isWinning,
                                            'bg-gradient-to-r from-primary-500/8 to-primary-400/5' => ! $isSelected && $isWinning,
                                        ])></div>
                                    </div>
                                @endif

                                {{-- Content --}}
                                <div class="relative flex items-center gap-3">
                                    {{-- Check indicator --}}
                                    <div @class([
                                        'flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 transition-all duration-200',
                                        'border-primary-500 bg-primary-500 text-white' => $isSelected,
                                        'border-gray-300 bg-transparent group-hover/vote:border-gray-400 dark:border-gray-600 dark:group-hover/vote:border-gray-500' => ! $isSelected,
                                    ])>
                                        @if ($isSelected)
                                            <svg class="h-3 w-3" viewBox="0 0 12 12" fill="none">
                                                <path d="M3.5 6L5.5 8L8.5 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        @endif
                                    </div>

                                    {{-- Option text --}}
                                    <span @class([
                                        'flex-1 text-sm font-medium transition-colors duration-200',
                                        'text-primary-700 dark:text-primary-300' => $isSelected,
                                        'text-gray-700 dark:text-gray-300' => ! $isSelected,
                                    ])>
                                        {{ $option }}
                                    </span>

                                    {{-- Percentage + vote count --}}
                                    @if ($totalVotes > 0)
                                        <div class="flex shrink-0 items-center gap-1.5">
                                            <span @class([
                                                'text-xs font-semibold tabular-nums',
                                                'text-primary-600 dark:text-primary-400' => $isSelected || $isWinning,
                                                'text-gray-400 dark:text-gray-500' => ! $isSelected && ! $isWinning,
                                            ])>
                                                {{ $percentage }}%
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>

                {{-- Footer --}}
                @if ($totalVotes > 0)
                    <div class="flex items-center gap-2 px-1">
                        <p class="shrink-0 text-[11px] font-medium text-gray-400 dark:text-gray-500">
                            {{ trans_choice('codenzia-comments::codenzia-comments.comment_types.vote_count', $totalVotes, ['count' => $totalVotes]) }}
                        </p>
                        <div class="h-px flex-1 bg-gradient-to-l from-gray-200 via-gray-200 to-transparent dark:from-gray-700 dark:via-gray-700"></div>
                    </div>
                @endif
            </div>
        @elseif ($comment->type === \Codenzia\FilamentComments\Enums\CommentType::Image)
            {{-- Image Rendering --}}
            @php
                $imageData = $comment->getDecodedComment();
                $images = $imageData['images'] ?? [];
                $caption = $imageData['caption'] ?? '';
            @endphp
            <div class="mt-2 space-y-2">
                @if (count($images) > 0)
                    <div @class([
                        'grid gap-2',
                        'grid-cols-1' => count($images) === 1,
                        'grid-cols-2' => count($images) >= 2,
                    ])>
                        @foreach ($images as $image)
                            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image) }}" target="_blank" class="block overflow-hidden rounded-lg">
                                <img
                                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image) }}"
                                    alt=""
                                    class="object-cover rounded-lg" style="width: 20%;"
                                    loading="lazy"
                                >
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        @else
            <div
                class="comment-body prose prose-sm mt-1 max-w-none text-gray-700 dark:prose-invert dark:text-gray-300"
                x-data
                x-init="$nextTick(() => window.__mentionPopoverManager && window.__mentionPopoverManager.bind($el, @js($mentionables)))"
            >
                {!! $comment->comment !!}
            </div>
        @endif

        {{-- Footer: Reactions + Reply + Replies Toggle --}}
        @php
            $reactions = $comment->getReactionsSummary();
            $userReaction = $comment->userReaction();
            $reactionTypes = config('codenzia-comments.reactions', []);
            $hasAnyReactions = collect($reactions)->sum() > 0;
        @endphp

        <div class="mt-2 flex flex-wrap items-center gap-2">
            {{-- Reaction Picker --}}
            @if ($canPost)
            <div class="relative" x-data="{ open: @entangle('showReactionPicker') }">
                <button
                    wire:click="toggleReactionPicker"
                    class="inline-flex items-center justify-center rounded-full p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/5 dark:hover:text-gray-300"
                    title="Add reaction"
                >
                    <x-filament::icon icon="heroicon-o-face-smile" class="h-4 w-4" />
                </button>

                {{-- Picker Popover --}}
                <div
                    x-show="open"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    @click.away="open = false"
                    class="absolute bottom-full left-0 z-50 mb-2 flex items-center gap-0.5 rounded-full bg-white px-2 py-1.5 shadow-lg ring-1 ring-gray-200/80 dark:bg-gray-800 dark:ring-gray-700"
                >
                    @foreach ($reactionTypes as $type => $emoji)
                        <button
                            wire:click="toggleReaction('{{ $type }}')"
                            class="rounded-full p-1 text-lg transition-transform duration-100 hover:scale-125 hover:bg-gray-100 dark:hover:bg-gray-700"
                            title="{{ __('codenzia-comments::codenzia-comments.reactions.' . $type) }}"
                        >
                            {{ $emoji }}
                        </button>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Active Reaction Badges --}}
            @if ($hasAnyReactions)
                @foreach ($reactionTypes as $type => $emoji)
                    @php
                        $count = $reactions[$type] ?? 0;
                        if ($count <= 0) continue;
                        $isActive = $userReaction && $userReaction->reaction_type === $type;
                    @endphp
                    <button
                        @if ($canPost) wire:click="toggleReaction('{{ $type }}')" @endif
                        @class([
                            'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium transition-all duration-150',
                            'bg-primary-50 text-primary-700 ring-1 ring-primary-200 dark:bg-primary-500/10 dark:text-primary-300 dark:ring-primary-500/30' => $isActive,
                            'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-white/5 dark:text-gray-400 dark:hover:bg-white/10' => ! $isActive,
                            'cursor-default' => ! $canPost,
                        ])
                        title="{{ __('codenzia-comments::codenzia-comments.reactions.' . $type) }}"
                    >
                        <span class="text-sm leading-none">{{ $emoji }}</span>
                        <span>{{ $count }}</span>
                    </button>
                @endforeach
            @endif

            {{-- Divider --}}
            @if (($hasAnyReactions || $canPost) && ($canPost || $comment->replies->count() > 0))
                <span class="mx-0.5 h-3.5 w-px bg-gray-200 dark:bg-gray-700"></span>
            @endif

            {{-- Reply Button --}}
            @if ($canPost)
                <button
                    wire:click="toggleReplyForm"
                    class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-xs font-medium text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300"
                >
                    <x-filament::icon icon="heroicon-o-chat-bubble-left" class="h-3.5 w-3.5" />
                    {{ __('codenzia-comments::codenzia-comments.comments.reply') }}
                </button>
            @endif

            {{-- Replies Toggle --}}
            @if ($comment->replies->count() > 0)
                <button
                    wire:click="toggleReplies"
                    class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-xs font-medium text-primary-600 transition-colors hover:bg-primary-50 dark:text-primary-400 dark:hover:bg-primary-500/10"
                >
                    <x-filament::icon
                        :icon="$showReplies ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down'"
                        class="h-3.5 w-3.5"
                    />
                    {{ trans_choice('codenzia-comments::codenzia-comments.comments.replies_count', $comment->replies->count(), ['count' => $comment->replies->count()]) }}
                </button>
            @endif
        </div>

        {{-- Reply Form --}}
        @if ($showReplyForm)
            <div
                class="mt-3 border-l-2 border-primary-200 pl-4 dark:border-primary-500/30"
                x-data="{ uploading: false }"
                x-on:livewire-upload-start="uploading = true"
                x-on:livewire-upload-finish="uploading = false; if ($refs.replyImageInput{{ $comment->id }}) $refs.replyImageInput{{ $comment->id }}.value = ''; if ($refs.replyFileInput{{ $comment->id }}) $refs.replyFileInput{{ $comment->id }}.value = ''"
                x-on:livewire-upload-error="uploading = false; if ($refs.replyImageInput{{ $comment->id }}) $refs.replyImageInput{{ $comment->id }}.value = ''; if ($refs.replyFileInput{{ $comment->id }}) $refs.replyFileInput{{ $comment->id }}.value = ''"
            >
                {{-- Hidden file input for image upload --}}
                <input
                    type="file"
                    wire:model="tempImages"
                    accept="image/*"
                    multiple
                    class="hidden"
                    x-ref="replyImageInput{{ $comment->id }}"
                />

                {{-- Hidden file input for document upload --}}
                <input
                    type="file"
                    wire:model="tempFiles"
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.ppt,.pptx"
                    multiple
                    class="hidden"
                    x-ref="replyFileInput{{ $comment->id }}"
                />

                <div class="comment-composer reply-composer-{{ $comment->id }} rounded-xl dark:bg-[#16181C]">
                    <div class="comment-composer__editor">
                        {{ $this->replyForm }}
                    </div>

                    {{-- Bottom toolbar --}}
                    <div class="relative flex items-center gap-0.5 border-t border-gray-700 dark:border-gray-700 px-2 py-1.5">
                        <div class="relative flex items-center gap-0.5">
                            {{-- @ mention --}}
                            <button
                                class="flex items-center justify-center rounded-md p-1.5 text-gray-400 transition-colors hover:bg-[#212427] hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300"
                                title="{{ __('codenzia-comments::codenzia-comments.comments.mention_hint') }}"
                                onclick="window.__triggerMention(this.closest('.comment-composer'))"
                            >
                                <x-filament::icon icon="heroicon-o-at-symbol" class="h-4.5 w-4.5" />
                            </button>

                            {{-- Image shortcut --}}
                            <button
                                @click="$refs.replyImageInput{{ $comment->id }}.click()"
                                class="flex items-center justify-center rounded-md p-1.5 text-gray-400 transition-colors hover:bg-[#212427] hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300"
                                title="{{ __('codenzia-comments::codenzia-comments.comment_types.image') }}"
                            >
                                <x-filament::icon icon="heroicon-o-photo" class="h-4.5 w-4.5" />
                            </button>

                            {{-- File upload --}}
                            <button
                                @click="$refs.replyFileInput{{ $comment->id }}.click()"
                                class="flex items-center justify-center rounded-md p-1.5 text-gray-400 transition-colors hover:bg-[#212427] hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300"
                                title="{{ __('codenzia-comments::codenzia-comments.comment_types.file') ?? 'Attach file' }}"
                            >
                                <x-filament::icon icon="heroicon-o-paper-clip" class="h-4.5 w-4.5" />
                            </button>

                            {{-- Emoji picker --}}
                            <div class="relative" x-data="{ emojiOpen: false }">
                                <button
                                    @click="emojiOpen = !emojiOpen"
                                    class="flex items-center justify-center rounded-md p-1.5 text-gray-400 transition-colors hover:bg-[#212427] hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300"
                                    title="{{ __('codenzia-comments::codenzia-comments.comments.emoji') ?? 'Emoji' }}"
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
                                    class="absolute left-0 z-50 mb-2 bottom-full w-64 max-h-48 overflow-y-auto rounded-lg bg-white p-2 shadow-lg ring-1 ring-gray-200/80 dark:bg-[#16181C] dark:ring-gray-700"
                                >
                                    <div class="grid grid-cols-8 gap-0.5">
                                        @foreach (['😀','😂','😊','😍','🥰','😎','🤔','😏','😢','😭','😡','🤯','🥳','😴','🤗','😈','👍','👎','👏','🙌','🤝','✌️','🔥','❤️','💯','⭐','🎉','✅','❌','💡','🚀','👀','💬','📌','🏆','💪'] as $emoji)
                                            <button
                                                type="button"
                                                @click="window.__insertEmoji($el.closest('.comment-composer'), '{{ $emoji }}'); emojiOpen = false"
                                                class="flex items-center justify-center rounded p-1 text-lg transition-transform duration-100 hover:scale-125 hover:bg-gray-100 dark:hover:bg-white/10"
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

                        {{-- Cancel button --}}
                        <button
                            wire:click="toggleReplyForm"
                            class="flex items-center justify-center rounded-lg p-1.5 text-gray-400 transition-colors hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300"
                            title="{{ __('codenzia-comments::codenzia-comments.comments.cancel') }}"
                        >
                            <x-filament::icon icon="heroicon-o-x-mark" class="h-4 w-4" />
                        </button>

                        {{-- Send button --}}
                        <button
                            wire:click="reply"
                            wire:loading.attr="disabled"
                            :disabled="uploading"
                            class="flex items-center justify-center rounded-lg dark:hover:bg-[#212427] p-1.5 disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="reply">
                                <x-filament::icon icon="heroicon-o-paper-airplane" class="h-4 w-4" />
                            </span>
                            <span wire:loading wire:target="reply">
                                <x-filament::loading-indicator class="h-4 w-4" />
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Nested Replies --}}
        @if ($showReplies && $comment->replies->count() > 0)
            <div class="mt-3 space-y-1 border-l-2 border-gray-200 pl-3 dark:border-gray-700/60">
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

@script
<script>
if (!window.__mentionPopoverManager) {
    window.__mentionPopoverManager = (function () {
        let popoverEl = null;
        let hideTimeout = null;

        function getPopover() {
            if (popoverEl) return popoverEl;

            popoverEl = document.createElement('div');
            popoverEl.className = 'fixed z-[9999] w-64 rounded-xl bg-white shadow-xl ring-1 ring-gray-200/80 dark:bg-gray-800 dark:ring-gray-700 transition-all duration-150';
            popoverEl.style.cssText = 'pointer-events:auto;opacity:0;transform:translateY(4px) scale(0.95);display:none;';
            popoverEl.innerHTML = `
                <a id="mp-link" href="#" class="block p-4 no-underline transition-colors hover:bg-gray-50 rounded-xl dark:hover:bg-white/5">
                    <div class="flex items-center gap-3">
                        <div id="mp-avatar-wrap" class="shrink-0" style="display:none;">
                            <img id="mp-avatar" src="" alt="" class="h-10 w-10 rounded-full object-cover ring-2 ring-white shadow-sm dark:ring-gray-700">
                        </div>
                        <div id="mp-initials-wrap" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-50 ring-2 ring-white shadow-sm dark:bg-primary-500/10 dark:ring-gray-700" style="display:none;">
                            <span id="mp-initials" class="text-sm font-semibold text-primary-600 dark:text-primary-400"></span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p id="mp-name" class="truncate text-sm font-semibold text-gray-900 dark:text-white"></p>
                            <p id="mp-email" class="truncate text-xs text-gray-500 dark:text-gray-400" style="display:none;"></p>
                        </div>
                    </div>
                </a>`;

            document.body.appendChild(popoverEl);

            popoverEl.addEventListener('mouseenter', () => clearTimeout(hideTimeout));
            popoverEl.addEventListener('mouseleave', () => scheduleHide());

            return popoverEl;
        }

        function show(user, anchorRect) {
            console.log(user);

            clearTimeout(hideTimeout);

            const el = getPopover();
            const link = el.querySelector('#mp-link');
            const avatarWrap = el.querySelector('#mp-avatar-wrap');
            const avatar = el.querySelector('#mp-avatar');
            const initialsWrap = el.querySelector('#mp-initials-wrap');
            const initials = el.querySelector('#mp-initials');
            const name = el.querySelector('#mp-name');
            const email = el.querySelector('#mp-email');

            link.href = user.link || '#';
            name.textContent = user.key || '';

            if (user.avatar) {
                avatar.src = user.avatar;
                avatar.alt = user.key || '';
                avatarWrap.style.display = '';
                initialsWrap.style.display = 'none';
            } else {
                avatarWrap.style.display = 'none';
                initialsWrap.style.display = '';
                initials.textContent = (user.key || '').substring(0, 2).toUpperCase();
            }

            if (user.email) {
                email.textContent = user.email;
                email.style.display = '';
            } else {
                email.style.display = 'none';
            }

            el.style.display = 'block';
            el.style.left = anchorRect.left + 'px';
            el.style.top = (anchorRect.bottom + 6) + 'px';

            requestAnimationFrame(() => {
                el.style.opacity = '1';
                el.style.transform = 'translateY(0) scale(1)';
            });
        }

        function hide() {
            if (!popoverEl) return;
            popoverEl.style.opacity = '0';
            popoverEl.style.transform = 'translateY(4px) scale(0.95)';
            setTimeout(() => { if (popoverEl) popoverEl.style.display = 'none'; }, 150);
        }

        function scheduleHide() {
            hideTimeout = setTimeout(hide, 250);
        }

        function bind(containerEl, mentionables) {
            const links = containerEl.querySelectorAll('a.tribute-mention');
            links.forEach(link => {
                if (link.__mpBound) return;
                link.__mpBound = true;

                link.addEventListener('mouseenter', () => {
                    const mentionText = (link.textContent || '').replace(/^[^a-zA-Z0-9\s]/, '').trim();
                    const user = (mentionables || []).find(
                        u => u.key && u.key.toLowerCase() === mentionText.toLowerCase()
                    );
                    if (!user) return;
                    console.log(user);

                    show(user, link.getBoundingClientRect());
                });

                link.addEventListener('mouseleave', () => scheduleHide());
            });
        }

        return { bind };
    })();
}
</script>
@endscript
