<div
    id="comment-{{ $comment->id }}"
    x-data="{ showCard: false }"
    @click.outside="showCard = false"
    class="comment-item group relative flex gap-3 px-3 py-2 rounded hover:bg-gray-50 dark:hover:bg-white/5 transition-colors duration-100"
    x-init="
        if (window.location.hash === '#comment-{{ $comment->id }}') {
            $nextTick(() => {
                $el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                $el.classList.add('ring-2', 'ring-primary-400', 'ring-offset-1', 'dark:ring-offset-gray-900');
                setTimeout(() => $el.classList.remove('ring-2', 'ring-primary-400', 'ring-offset-1', 'dark:ring-offset-gray-900'), 3000);
            });
        }
    ">
    {{-- Avatar + User Profile Card --}}
    @php
        // Prefer Filament's avatar method, fall back to column-based lookup
        $avatarUrl = method_exists($comment->commentator, 'getFilamentAvatarUrl')
            ? $comment->commentator->getFilamentAvatarUrl()
            : null;

        if (! $avatarUrl) {
            $avatarColumn = config('filament-comments.mentionable.column.avatar', 'avatar_url');
            $avatarPath = $comment->commentator->{$avatarColumn} ?? null;
            if ($avatarPath) {
                $avatarUrl = filter_var($avatarPath, FILTER_VALIDATE_URL)
                    ? $avatarPath
                    : asset('storage/' . $avatarPath);
            }
        }

        $emailColumn = config('filament-comments.mentionable.column.email', 'email');
        $userEmail = $comment->commentator->{$emailColumn} ?? null;
        $userTitle = $comment->commentator->title ?? null;
        $userRole = $comment->commentator->role_name ?? null;
        $userDepartment = method_exists($comment->commentator, 'department') && $comment->commentator->relationLoaded('department')
            ? $comment->commentator->department?->name
            : ($comment->commentator->department->name ?? null);
        $isCurrentUser = $comment->commentator->id === auth()->id();
    @endphp

    <div class="flex-shrink-0 pt-0.5 relative">
        <button type="button" @click="showCard = !showCard" class="cursor-pointer focus:outline-none">
            @if ($avatarUrl)
                <img src="{{ $avatarUrl }}" alt="{{ $comment->commentator->name }}"
                    class="h-9 w-9 rounded-full object-cover hover:ring-2 hover:ring-primary-400 transition-all">
            @else
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-500/15 hover:ring-2 hover:ring-primary-400 transition-all">
                    <span class="text-xs font-semibold text-primary-600 dark:text-primary-400">
                        {{ strtoupper(substr($comment->commentator->name, 0, 2)) }}
                    </span>
                </div>
            @endif
        </button>

        {{-- User Profile Card Popover --}}
        <div x-show="showCard" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            class="absolute left-0 top-full z-50 mt-1 w-64 rounded-xl border border-gray-200 bg-white shadow-xl dark:border-white/10 dark:bg-gray-900">
            <div class="p-4">
                {{-- Card Header: Large avatar + name --}}
                <div class="flex items-center gap-3 mb-3">
                    @if ($avatarUrl)
                        <img src="{{ $avatarUrl }}" alt="{{ $comment->commentator->name }}"
                            class="h-12 w-12 rounded-full object-cover">
                    @else
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-500/15">
                            <span class="text-sm font-semibold text-primary-600 dark:text-primary-400">
                                {{ strtoupper(substr($comment->commentator->name, 0, 2)) }}
                            </span>
                        </div>
                    @endif
                    <div class="min-w-0">
                        <div class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                            {{ $comment->commentator->name }}
                        </div>
                        @if ($userTitle || $userRole)
                            <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                {{ $userTitle ?: $userRole }}
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Details --}}
                <div class="space-y-1.5 mb-3">
                    @if ($userEmail)
                        <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-3.5 w-3.5 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                            <span class="truncate">{{ $userEmail }}</span>
                        </div>
                    @endif
                    @if ($userDepartment)
                        <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-3.5 w-3.5 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
                            <span class="truncate">{{ $userDepartment }}</span>
                        </div>
                    @endif
                    @if ($userRole && $userTitle)
                        <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-3.5 w-3.5 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                            <span class="truncate">{{ $userRole }}</span>
                        </div>
                    @endif
                </div>

                {{-- Action: Direct Message (only if not current user) --}}
                @if (! $isCurrentUser)
                    <button
                        wire:click="startDirectMessage({{ $comment->commentator->id }})"
                        @click="showCard = false"
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-primary-500 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-primary-600 dark:bg-primary-600 dark:hover:bg-primary-500"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-3.5 w-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" /></svg>
                        {{ __('filament-comments::messages.user_card.message') }}
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Content --}}
    <div class="min-w-0 flex-1">
        {{-- Header: Name · Timestamp · Actions --}}
        <div class="flex items-center gap-2">
            <button type="button" @click="showCard = !showCard"
                class="text-sm font-semibold text-gray-900 dark:text-white truncate cursor-pointer hover:underline">
                {{ $comment->commentator->name }}
            </button>
            <span class="shrink-0 text-[11px] text-gray-400 dark:text-gray-500"
                title="{{ $comment->created_at->format('M d, Y \a\t h:i A') }}">
                {{ $comment->created_at->diffForHumans() }}
            </span>

            @if (
                $comment->created_at->ne($comment->updated_at) &&
                    $comment->type !== \Codenzia\FilamentComments\Enums\CommentType::Vote)
                <span class="shrink-0 text-[10px] italic text-gray-400 dark:text-gray-500">
                    {{ __('filament-comments::messages.comments.edited') ?? 'edited' }}
                </span>
            @endif

            {{-- Resolved badge --}}
            @if ($comment->is_resolved && ! $comment->isReply())
                <span class="inline-flex items-center gap-1 rounded-full bg-success-500 px-2 py-0.5 text-[10px] font-medium text-gray-900 dark:bg-success-600 dark:text-gray-900">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-3 w-3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    {{ __('Resolved') }}
                    @if ($comment->resolvedBy)
                        {{ __('by') }} {{ $comment->resolvedBy->name }}
                    @endif
                </span>
            @endif

            {{-- Inline action buttons (visible on hover) --}}
            <div class="flex items-center gap-0.5 opacity-0 transition-opacity duration-100 group-hover:opacity-100">
                {{-- Bookmark toggle --}}
                <button wire:click="toggleBookmark"
                    @class([
                        'rounded p-0.5 transition-colors',
                        'text-amber-500 dark:text-amber-400' => $isBookmarked,
                        'text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300' => ! $isBookmarked,
                    ])
                    title="{{ $isBookmarked ? __('Remove bookmark') : __('Bookmark') }}">
                    <x-filament::icon :icon="$isBookmarked ? 'heroicon-s-bookmark' : 'heroicon-o-bookmark'" class="h-3.5 w-3.5" />
                </button>

                {{-- Pin / Unpin (root comments only) --}}
                @if (! $comment->isReply())
                    @if ($comment->is_pinned)
                        <button wire:click="unpinComment"
                            class="rounded p-0.5 text-amber-500 transition-colors hover:text-amber-600 dark:text-amber-400 dark:hover:text-amber-300"
                            title="{{ __('Unpin') }}">
                            <x-filament::icon icon="heroicon-s-map-pin" class="h-3.5 w-3.5" />
                        </button>
                    @else
                        <button wire:click="pinComment"
                            class="rounded p-0.5 text-gray-400 transition-colors hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                            title="{{ __('Pin comment') }}">
                            <x-filament::icon icon="heroicon-o-map-pin" class="h-3.5 w-3.5" />
                        </button>
                    @endif

                    {{-- Resolve / Unresolve --}}
                    @if ($comment->is_resolved)
                        <button wire:click="unresolveThread"
                            class="rounded p-0.5 text-green-500 transition-colors hover:text-green-600 dark:text-green-400 dark:hover:text-green-300"
                            title="{{ __('Unresolve') }}">
                            <x-filament::icon icon="heroicon-s-check-circle" class="h-3.5 w-3.5" />
                        </button>
                    @else
                        <button wire:click="resolveThread"
                            class="rounded p-0.5 text-gray-400 transition-colors hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                            title="{{ __('Resolve thread') }}">
                            <x-filament::icon icon="heroicon-o-check-circle" class="h-3.5 w-3.5" />
                        </button>
                    @endif
                @endif

                @if (auth()->id() === $comment->user_id)
                    @if ($comment->type === null || $comment->type === \Codenzia\FilamentComments\Enums\CommentType::Text)
                        <button wire:click="edit"
                            class="rounded p-0.5 text-gray-400 transition-colors hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                            title="{{ __('filament-comments::messages.comments.edit') }}">
                            <x-filament::icon icon="heroicon-o-pencil-square" class="h-3.5 w-3.5" />
                        </button>
                    @endif
                    <button wire:click="delete"
                        wire:confirm="{{ __('filament-comments::messages.comments.delete_confirm') }}"
                        class="rounded p-0.5 text-gray-400 transition-colors hover:text-red-500 dark:text-gray-500 dark:hover:text-red-400"
                        title="{{ __('filament-comments::messages.comments.delete') }}">
                        <x-filament::icon icon="heroicon-o-trash" class="h-3.5 w-3.5" />
                    </button>
                @endif
            </div>

        </div>

        {{-- Body --}}
        @if ($showEditForm)
            <div class="mt-2 space-y-2">
                {{ $this->editForm }}
                <div class="flex items-center gap-2">
                    <x-filament::button wire:click="updateComment" size="xs" color="primary">
                        <span wire:loading.remove
                            wire:target="updateComment">{{ __('filament-comments::messages.comments.save') }}</span>
                        <span wire:loading wire:target="updateComment">
                            <x-filament::loading-indicator class="h-4 w-4" />
                        </span>
                    </x-filament::button>
                    <x-filament::button wire:click="toggleEditForm" size="xs" color="gray" outlined>
                        {{ __('filament-comments::messages.comments.cancel') }}
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
            <div class="mt-2 max-w-2xl">
                {{-- Question --}}
                <p class="text-[15px] font-bold text-gray-900 dark:text-white">{{ $question }}</p>

                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                    {{ $totalVotes > 0
                        ? trans_choice('filament-comments::messages.comment_types.poll_count', $totalVotes, [
                            'count' => $totalVotes,
                        ])
                        : __('filament-comments::messages.comment_types.poll') }}
                </p>

                {{-- Options --}}
                <div class="mt-3 space-y-2">
                    @php $maxVotes = collect($votes)->countBy()->max() ?? 0; @endphp
                    @foreach ($options as $index => $option)
                        @php
                            $optionVotes = collect($votes)->filter(fn($v) => $v === $index)->count();
                            $percentage = $totalVotes > 0 ? round(($optionVotes / $totalVotes) * 100) : 0;
                            $isSelected = $userVote === $index;
                            $isWinning = $totalVotes > 0 && $optionVotes === $maxVotes && $optionVotes > 0;
                            $optionLabel = is_array($option) ? $option['option'] ?? reset($option) : $option;
                        @endphp
                        <button wire:click="$parent.castVote({{ $comment->id }}, {{ $index }})"
                            class="group/vote relative w-full overflow-hidden rounded-lg text-left transition-all duration-150 hover:brightness-110 active:scale-[0.99]">
                            {{-- Track background --}}
                            <div @class([
                                'relative h-9 w-full rounded-lg',
                                'bg-gray-100 dark:bg-white/[0.06]',
                            ])>
                                {{-- Filled bar --}}
                                <div @class([
                                    'absolute inset-y-0 left-0 rounded-lg transition-all duration-500 ease-out',
                                    'bg-primary-500/80 dark:bg-primary-500/60' => $isWinning || $isSelected,
                                    'bg-gray-200 dark:bg-white/[0.08]' =>
                                        !$isSelected && !$isWinning && $totalVotes > 0,
                                ])
                                    style="width: {{ $totalVotes > 0 ? max($percentage, 2) : 0 }}%"></div>

                                {{-- Text overlay --}}
                                <div class="relative flex h-full items-center justify-between gap-2 px-3">
                                    <div class="flex items-center gap-2 truncate">
                                        @if ($isSelected)
                                            <svg class="h-4 w-4 shrink-0 text-primary-600 dark:text-primary-400"
                                                viewBox="0 0 16 16" fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14Zm3.28-8.72a.75.75 0 0 0-1.06-1.06L7 8.44 5.78 7.22a.75.75 0 0 0-1.06 1.06l1.75 1.75a.75.75 0 0 0 1.06 0l3.75-3.75Z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        @endif
                                        <span @class([
                                            'truncate text-sm font-semibold',
                                            'text-gray-900 dark:text-white' => $isSelected || $isWinning,
                                            'text-gray-700 dark:text-gray-300' => !$isSelected && !$isWinning,
                                        ])>
                                            {{ $optionLabel }}
                                        </span>
                                    </div>
                                    @if ($totalVotes > 0)
                                        <span @class([
                                            'shrink-0 text-sm font-bold tabular-nums',
                                            'text-primary-700 dark:text-primary-300' => $isSelected || $isWinning,
                                            'text-gray-400 dark:text-gray-500' => !$isSelected && !$isWinning,
                                        ])>
                                            {{ $percentage }}%
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>
        @elseif ($comment->type === \Codenzia\FilamentComments\Enums\CommentType::Event)
            {{-- Event Rendering --}}
            @php
                $eventData = $comment->getDecodedComment();
                $eventTitle = $eventData['title'] ?? '';
                $eventDate = $eventData['date'] ?? '';
                $eventDescription = $eventData['description'] ?? '';
                $parsedDate = $eventDate ? \Carbon\Carbon::parse($eventDate) : null;
                $isPast = $parsedDate && $parsedDate->isPast();
                $responses = $eventData['responses'] ?? [];
                $goingCount = collect($responses)->filter(fn($v) => $v === 'going')->count();
                $maybeCount = collect($responses)->filter(fn($v) => $v === 'maybe')->count();
                $notGoingCount = collect($responses)->filter(fn($v) => $v === 'not_going')->count();
                $userStatus = $responses[(string) auth()->id()] ?? null;
            @endphp
            <div class="mt-3">
                <div @class([
                    'relative rounded-xl border border-gray-200 dark:border-gray-700',
                ])>


                    <div class="flex gap-4 p-4">
                        {{-- Date block --}}
                        @if ($parsedDate)
                            <div class="flex shrink-0 flex-col items-center">
                                <div @class([
                                    'flex h-14 w-14 flex-col items-center justify-center rounded-xl',
                                    'bg-primary-50 dark:bg-primary-500/10' => !$isPast,
                                    'bg-gray-100 dark:bg-white/5' => $isPast,
                                ])>
                                    <span @class([
                                        'text-[10px] font-bold uppercase leading-none',
                                        'text-primary-500' => !$isPast,
                                        'text-gray-400 dark:text-gray-500' => $isPast,
                                    ])>
                                        {{ $parsedDate->format('M') }}
                                    </span>
                                    <span @class([
                                        'text-xl font-bold leading-tight',
                                        'text-primary-700 dark:text-primary-300' => !$isPast,
                                        'text-gray-500 dark:text-gray-400' => $isPast,
                                    ])>
                                        {{ $parsedDate->format('d') }}
                                    </span>
                                </div>
                            </div>
                        @endif

                        {{-- Details --}}
                        <div class="min-w-0 flex-1">
                            <h4 @class([
                                'text-sm font-semibold',
                                'text-gray-900 dark:text-white' => !$isPast,
                                'text-gray-500 dark:text-gray-400 line-through' => $isPast,
                            ])>
                                {{ $eventTitle }}
                            </h4>

                            @if ($parsedDate)
                                <div class="mt-1 flex items-center gap-1.5">
                                    <x-filament::icon icon="heroicon-o-clock" @class([
                                        'h-3.5 w-3.5',
                                        'text-gray-400 dark:text-gray-500' => !$isPast,
                                        'text-gray-300 dark:text-gray-600' => $isPast,
                                    ]) />
                                    <span @class([
                                        'text-xs',
                                        'text-gray-500 dark:text-gray-400' => !$isPast,
                                        'text-gray-400 dark:text-gray-500' => $isPast,
                                    ])>
                                        {{ $parsedDate->format('l, M d, Y \a\t g:i A') }}
                                    </span>
                                </div>
                            @endif

                            @if ($eventDescription)
                                <p class="mt-2 text-xs leading-relaxed text-gray-500 dark:text-gray-400">
                                    {{ $eventDescription }}
                                </p>
                            @endif
                        </div>
                        <div class="flex flex-row gap-2">
                            {{-- Respond to event dropdown --}}
                            @if (!$isPast)
                                @if (config('filament-comments.enable_add_to_calendar'))
                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        <button type="button" wire:click="$parent.addToCalendar({{ $comment->id }})"
                                            class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-0.5 text-[11px] border-gray-200 dark:border-gray-700 font-medium  transition-colors text-primary-700 dark:text-primary-300">
                                            {{ __('filament-comments::messages.comment_types.add_to_calendar') }}
                                            <x-filament::icon icon="heroicon-o-calendar" class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                @endif
                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <div x-data="{ open: false }" class="relative">
                                        <button type="button" @click="open = !open" @class([
                                            'inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-0.5 text-[11px] border-gray-200 dark:border-gray-700 font-medium  transition-colors',
                                            'text-primary-700 dark:text-primary-300' => $userStatus === 'going',
                                            'border-amber-500 bg-amber-500/10 text-amber-700 dark:text-amber-300' =>
                                                $userStatus === 'maybe',
                                            'border-gray-400 bg-gray-200 text-gray-700 dark:border-gray-600 dark:bg-white/10 dark:text-gray-200' =>
                                                $userStatus === 'not_going',
                                            'border-gray-200 bg-white text-gray-600 hover:border-gray-300 dark:border-gray-700 dark:bg-white/5 dark:text-gray-300 dark:hover:border-gray-500' => !$userStatus,
                                        ])>
                                            <span class="flex items-center gap-1.5">
                                                <span class="text-sm leading-none">
                                                    @switch($userStatus)
                                                        @case('going')
                                                            👍
                                                        @break

                                                        @case('maybe')
                                                            🤔
                                                        @break

                                                        @case('not_going')
                                                            🙅
                                                        @break

                                                        @default
                                                            👋
                                                    @endswitch
                                                </span>
                                                <span>
                                                    @switch($userStatus)
                                                        @case('going')
                                                            {{ __('filament-comments::messages.comment_types.event_going') }}
                                                        @break

                                                        @case('maybe')
                                                            {{ __('filament-comments::messages.comment_types.event_maybe') }}
                                                        @break

                                                        @case('not_going')
                                                            {{ __('filament-comments::messages.comment_types.event_not_going') }}
                                                        @break

                                                        @default
                                                            {{ __('filament-comments::messages.comment_types.event_status') }}
                                                    @endswitch
                                                </span>
                                            </span>
                                            <x-filament::icon icon="heroicon-o-chevron-down"
                                                class="ml-1 h-3 w-3 text-gray-400 dark:text-gray-500 transition-transform"
                                                x-bind:class="{ 'rotate-180': open }" />
                                        </button>

                                        <div x-show="open" x-transition:enter="transition ease-out duration-100"
                                            x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                                            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                            x-transition:leave="transition ease-in duration-75"
                                            x-transition:leave-start="opacity-100 scale-100"
                                            x-transition:leave-end="opacity-0 scale-95" @click.away="open = false"
                                            class="absolute left-0 z-50 mt-1 w-40 rounded-lg bg-white py-1 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                                            <button type="button"
                                                wire:click="$parent.respondToEvent({{ $comment->id }}, 'going')"
                                                @click="open = false"
                                                class="flex w-full items-center justify-between px-3 py-1.5 text-[11px] text-gray-700 transition-colors hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5">
                                                <span class="flex items-center gap-1.5">
                                                    <span>👍</span>
                                                    <span>{{ __('filament-comments::messages.comment_types.event_going') }}</span>
                                                </span>
                                                @if ($goingCount > 0)
                                                    <span
                                                        class="text-[10px] text-gray-400">({{ $goingCount }})</span>
                                                @endif
                                            </button>

                                            <button type="button"
                                                wire:click="$parent.respondToEvent({{ $comment->id }}, 'maybe')"
                                                @click="open = false"
                                                class="flex w-full items-center justify-between px-3 py-1.5 text-[11px] text-gray-700 transition-colors hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5">
                                                <span class="flex items-center gap-1.5">
                                                    <span>🤔</span>
                                                    <span>{{ __('filament-comments::messages.comment_types.event_maybe') }}</span>
                                                </span>
                                                @if ($maybeCount > 0)
                                                    <span
                                                        class="text-[10px] text-gray-400">({{ $maybeCount }})</span>
                                                @endif
                                            </button>

                                            <button type="button"
                                                wire:click="$parent.respondToEvent({{ $comment->id }}, 'not_going')"
                                                @click="open = false"
                                                class="flex w-full items-center justify-between px-3 py-1.5 text-[11px] text-gray-700 transition-colors hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5">
                                                <span class="flex items-center gap-1.5">
                                                    <span>🙅</span>
                                                    <span>{{ __('filament-comments::messages.comment_types.event_not_going') }}</span>
                                                </span>
                                                @if ($notGoingCount > 0)
                                                    <span
                                                        class="text-[10px] text-gray-400">({{ $notGoingCount }})</span>
                                                @endif
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if ($isPast)
                                <span
                                    class="mt-2 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium text-gray-500 dark:text-gray-400">
                                    {{ __('filament-comments::messages.comment_types.event_past') }}
                                </span>
                            @endif

                        </div>
                    </div>
                </div>
            </div>
        @elseif ($comment->type === \Codenzia\FilamentComments\Enums\CommentType::Meeting)
            {{-- Meeting Rendering --}}
            @php
                $meetingData = $comment->getDecodedComment();
                $meetingTitle = $meetingData['title'] ?? '';
                $meetingStart = isset($meetingData['start_at']) ? \Carbon\Carbon::parse($meetingData['start_at']) : null;
                $meetingEnd = isset($meetingData['end_at']) ? \Carbon\Carbon::parse($meetingData['end_at']) : null;
                $meetingLink = $meetingData['google_meet_link'] ?? '';
                $meetingDescription = $meetingData['description'] ?? '';
                $isPast = $meetingStart && $meetingStart->isPast();
                $attendees = $meetingData['attendees'] ?? [];
                $attendingCount = collect($attendees)->filter(fn($v) => $v === 'attending')->count();
                $maybeCount = collect($attendees)->filter(fn($v) => $v === 'maybe')->count();
                $declinedCount = collect($attendees)->filter(fn($v) => $v === 'declined')->count();
                $userStatus = $attendees[(string) auth()->id()] ?? null;
            @endphp
            <div class="mt-3">
                <div @class([
                    'relative rounded-xl border',
                    'border-primary-200 dark:border-primary-700/50' => !$isPast,
                    'border-gray-200 dark:border-gray-700' => $isPast,
                ])>
                    <div class="flex gap-4 p-4">
                        {{-- Video camera icon block --}}
                        <div class="flex shrink-0 flex-col items-center">
                            <div @class([
                                'flex h-14 w-14 flex-col items-center justify-center rounded-xl',
                                'bg-primary-50 dark:bg-primary-500/10' => !$isPast,
                                'bg-gray-100 dark:bg-white/5' => $isPast,
                            ])>
                                <x-filament::icon icon="heroicon-o-video-camera" @class([
                                    'h-6 w-6',
                                    'text-primary-500' => !$isPast,
                                    'text-gray-400 dark:text-gray-500' => $isPast,
                                ]) />
                            </div>
                        </div>

                        {{-- Details --}}
                        <div class="min-w-0 flex-1">
                            <h4 @class([
                                'text-sm font-semibold',
                                'text-gray-900 dark:text-white' => !$isPast,
                                'text-gray-500 dark:text-gray-400 line-through' => $isPast,
                            ])>
                                {{ $meetingTitle }}
                            </h4>

                            @if ($meetingStart)
                                <div class="mt-1 flex items-center gap-1.5">
                                    <x-filament::icon icon="heroicon-o-clock" class="h-3.5 w-3.5 text-gray-400 dark:text-gray-500" />
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $meetingStart->format('l, M d, Y \a\t g:i A') }}
                                        @if ($meetingEnd)
                                            — {{ $meetingEnd->format('g:i A') }}
                                        @endif
                                    </span>
                                </div>
                            @endif

                            @if ($meetingDescription)
                                <p class="mt-2 text-xs leading-relaxed text-gray-500 dark:text-gray-400">
                                    {{ $meetingDescription }}
                                </p>
                            @endif

                            @if ($meetingLink)
                                <a href="{{ $meetingLink }}" target="_blank" rel="noopener"
                                    class="mt-2 inline-flex items-center gap-1.5 rounded-lg bg-primary-50 px-2.5 py-1 text-[11px] font-medium text-primary-700 transition-colors hover:bg-primary-100 dark:bg-primary-500/10 dark:text-primary-300 dark:hover:bg-primary-500/20">
                                    <x-filament::icon icon="heroicon-o-video-camera" class="h-3.5 w-3.5" />
                                    {{ __('filament-comments::messages.comment_types.meeting_join') }}
                                </a>
                            @endif
                        </div>

                        <div class="flex flex-row gap-2">
                            @if (!$isPast)
                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <div x-data="{ open: false }" class="relative">
                                        <button type="button" @click="open = !open" @class([
                                            'inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-0.5 text-[11px] border-gray-200 dark:border-gray-700 font-medium transition-colors',
                                            'text-primary-700 dark:text-primary-300' => $userStatus === 'attending',
                                            'border-amber-500 bg-amber-500/10 text-amber-700 dark:text-amber-300' => $userStatus === 'maybe',
                                            'border-gray-400 bg-gray-200 text-gray-700 dark:border-gray-600 dark:bg-white/10 dark:text-gray-200' => $userStatus === 'declined',
                                            'border-gray-200 bg-white text-gray-600 hover:border-gray-300 dark:border-gray-700 dark:bg-white/5 dark:text-gray-300 dark:hover:border-gray-500' => !$userStatus,
                                        ])>
                                            <span class="flex items-center gap-1.5">
                                                <span class="text-sm leading-none">
                                                    @switch($userStatus)
                                                        @case('attending') ✅ @break
                                                        @case('maybe') 🤔 @break
                                                        @case('declined') ❌ @break
                                                        @default 👋
                                                    @endswitch
                                                </span>
                                                <span>
                                                    @switch($userStatus)
                                                        @case('attending') {{ __('filament-comments::messages.comment_types.meeting_attending') }} @break
                                                        @case('maybe') {{ __('filament-comments::messages.comment_types.meeting_maybe') }} @break
                                                        @case('declined') {{ __('filament-comments::messages.comment_types.meeting_declined') }} @break
                                                        @default {{ __('filament-comments::messages.comment_types.meeting_rsvp') }}
                                                    @endswitch
                                                </span>
                                            </span>
                                            <x-filament::icon icon="heroicon-o-chevron-down"
                                                class="ml-1 h-3 w-3 text-gray-400 dark:text-gray-500 transition-transform"
                                                x-bind:class="{ 'rotate-180': open }" />
                                        </button>

                                        <div x-show="open" x-transition:enter="transition ease-out duration-100"
                                            x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                                            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                            x-transition:leave="transition ease-in duration-75"
                                            x-transition:leave-start="opacity-100 scale-100"
                                            x-transition:leave-end="opacity-0 scale-95" @click.away="open = false"
                                            class="absolute left-0 z-50 mt-1 w-40 rounded-lg bg-white py-1 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                                            <button type="button"
                                                wire:click="$parent.respondToMeeting({{ $comment->id }}, 'attending')"
                                                @click="open = false"
                                                class="flex w-full items-center justify-between px-3 py-1.5 text-[11px] text-gray-700 transition-colors hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5">
                                                <span class="flex items-center gap-1.5">
                                                    <span>✅</span>
                                                    <span>{{ __('filament-comments::messages.comment_types.meeting_attending') }}</span>
                                                </span>
                                                @if ($attendingCount > 0)
                                                    <span class="text-[10px] text-gray-400">({{ $attendingCount }})</span>
                                                @endif
                                            </button>
                                            <button type="button"
                                                wire:click="$parent.respondToMeeting({{ $comment->id }}, 'maybe')"
                                                @click="open = false"
                                                class="flex w-full items-center justify-between px-3 py-1.5 text-[11px] text-gray-700 transition-colors hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5">
                                                <span class="flex items-center gap-1.5">
                                                    <span>🤔</span>
                                                    <span>{{ __('filament-comments::messages.comment_types.meeting_maybe') }}</span>
                                                </span>
                                                @if ($maybeCount > 0)
                                                    <span class="text-[10px] text-gray-400">({{ $maybeCount }})</span>
                                                @endif
                                            </button>
                                            <button type="button"
                                                wire:click="$parent.respondToMeeting({{ $comment->id }}, 'declined')"
                                                @click="open = false"
                                                class="flex w-full items-center justify-between px-3 py-1.5 text-[11px] text-gray-700 transition-colors hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5">
                                                <span class="flex items-center gap-1.5">
                                                    <span>❌</span>
                                                    <span>{{ __('filament-comments::messages.comment_types.meeting_declined') }}</span>
                                                </span>
                                                @if ($declinedCount > 0)
                                                    <span class="text-[10px] text-gray-400">({{ $declinedCount }})</span>
                                                @endif
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if ($isPast)
                                <span class="mt-2 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium text-gray-500 dark:text-gray-400">
                                    {{ __('filament-comments::messages.comment_types.meeting_past') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @elseif ($comment->type === \Codenzia\FilamentComments\Enums\CommentType::Todo)
            {{-- Todo / Checklist Rendering --}}
            @php
                $todoData = $comment->getDecodedComment();
                $todoItems = $todoData['items'] ?? [];
                $doneCount = collect($todoItems)->filter(fn($item) => $item['done'] ?? false)->count();
                $totalCount = count($todoItems);
                $progressPercent = $totalCount > 0 ? round(($doneCount / $totalCount) * 100) : 0;
            @endphp
            <div class="mt-2 max-w-2xl">
                {{-- Progress bar --}}
                <div class="flex items-center gap-2 mb-2">
                    <div class="flex-1 h-1.5 rounded-full bg-gray-100 dark:bg-white/[0.06] overflow-hidden">
                        <div class="h-full rounded-full bg-primary-500 transition-all duration-500 ease-out"
                            style="width: {{ $progressPercent }}%"></div>
                    </div>
                    <span class="text-[11px] font-medium tabular-nums text-gray-500 dark:text-gray-400">
                        {{ $doneCount }}/{{ $totalCount }}
                    </span>
                </div>

                {{-- Items --}}
                <div class="space-y-1">
                    @foreach ($todoItems as $index => $item)
                        @php
                            $isDone = $item['done'] ?? false;
                            $priority = $item['priority'] ?? 'medium';
                            $priorityColors = [
                                'low' => 'text-gray-400 dark:text-gray-500',
                                'medium' => 'text-amber-500 dark:text-amber-400',
                                'high' => 'text-red-500 dark:text-red-400',
                            ];
                        @endphp
                        <button
                            wire:click="$parent.toggleTodoItem({{ $comment->id }}, {{ $index }})"
                            class="group/todo flex w-full items-center gap-2.5 rounded-lg px-2 py-1.5 text-left transition-colors hover:bg-gray-50 dark:hover:bg-white/5"
                        >
                            @if ($isDone)
                                <svg class="h-4.5 w-4.5 shrink-0 text-primary-500" viewBox="0 0 16 16" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14Zm3.28-8.72a.75.75 0 0 0-1.06-1.06L7 8.44 5.78 7.22a.75.75 0 0 0-1.06 1.06l1.75 1.75a.75.75 0 0 0 1.06 0l3.75-3.75Z" clip-rule="evenodd" />
                                </svg>
                            @else
                                <div class="h-4.5 w-4.5 shrink-0 rounded-full border-2 border-gray-300 dark:border-gray-600 transition-colors group-hover/todo:border-primary-400"></div>
                            @endif
                            <span @class([
                                'flex-1 text-sm',
                                'text-gray-400 line-through dark:text-gray-500' => $isDone,
                                'text-gray-800 dark:text-gray-200' => !$isDone,
                            ])>
                                {{ $item['title'] }}
                            </span>
                            @if ($priority !== 'medium')
                                <span class="{{ $priorityColors[$priority] ?? '' }} text-[10px] font-medium uppercase">
                                    {{ $priority }}
                                </span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        @elseif ($comment->type === \Codenzia\FilamentComments\Enums\CommentType::Survey)
            {{-- Survey Rendering --}}
            @php
                $surveyData = $comment->getDecodedComment();
                $surveyTitle = $surveyData['title'] ?? '';
                $surveyDescription = $surveyData['description'] ?? '';
                $surveyQuestions = $surveyData['questions'] ?? [];
                $totalResponders = collect($surveyQuestions)->flatMap(fn($q) => array_keys($q['responses'] ?? []))->unique()->count();
            @endphp
            <div class="mt-2 max-w-2xl">
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gray-50 dark:bg-white/[0.03] px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $surveyTitle }}</h4>
                        @if ($surveyDescription)
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $surveyDescription }}</p>
                        @endif
                        <p class="mt-1 text-[10px] text-gray-400 dark:text-gray-500">
                            {{ trans_choice('filament-comments::messages.comment_types.survey_response_count', $totalResponders, ['count' => $totalResponders]) }}
                        </p>
                    </div>

                    <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
                        @foreach ($surveyQuestions as $qIndex => $question)
                            @php
                                $qType = $question['type'] ?? 'text';
                                $qResponses = $question['responses'] ?? [];
                                $userAnswer = $qResponses[(string) auth()->id()] ?? null;
                            @endphp
                            <div class="px-4 py-3">
                                <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ $qIndex + 1 }}. {{ $question['content'] }}
                                </p>

                                @if ($qType === 'choice')
                                    @php
                                        $options = $question['options'] ?? [];
                                        $totalVotes = count($qResponses);
                                    @endphp
                                    <div class="space-y-1.5">
                                        @foreach ($options as $optIdx => $opt)
                                            @php
                                                $optVotes = collect($qResponses)->filter(fn($v) => $v === $optIdx)->count();
                                                $optPercent = $totalVotes > 0 ? round(($optVotes / $totalVotes) * 100) : 0;
                                                $isSelected = $userAnswer === $optIdx;
                                            @endphp
                                            <button
                                                wire:click="$parent.respondToSurvey({{ $comment->id }}, {{ $qIndex }}, {{ $optIdx }})"
                                                class="group/opt relative w-full overflow-hidden rounded-lg text-left transition-all duration-150 hover:brightness-110 active:scale-[0.99]"
                                            >
                                                <div class="relative h-8 w-full rounded-lg bg-gray-100 dark:bg-white/[0.06]">
                                                    <div @class([
                                                        'absolute inset-y-0 left-0 rounded-lg transition-all duration-500 ease-out',
                                                        'bg-primary-500/80 dark:bg-primary-500/60' => $isSelected,
                                                        'bg-gray-200 dark:bg-white/[0.08]' => !$isSelected && $totalVotes > 0,
                                                    ])
                                                        style="width: {{ $totalVotes > 0 ? max($optPercent, 2) : 0 }}%"></div>
                                                    <div class="relative flex h-full items-center justify-between gap-2 px-3">
                                                        <div class="flex items-center gap-2 truncate">
                                                            @if ($isSelected)
                                                                <svg class="h-3.5 w-3.5 shrink-0 text-primary-600 dark:text-primary-400" viewBox="0 0 16 16" fill="currentColor">
                                                                    <path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14Zm3.28-8.72a.75.75 0 0 0-1.06-1.06L7 8.44 5.78 7.22a.75.75 0 0 0-1.06 1.06l1.75 1.75a.75.75 0 0 0 1.06 0l3.75-3.75Z" clip-rule="evenodd" />
                                                                </svg>
                                                            @endif
                                                            <span class="truncate text-xs font-medium text-gray-700 dark:text-gray-300">{{ $opt }}</span>
                                                        </div>
                                                        @if ($totalVotes > 0)
                                                            <span class="shrink-0 text-[11px] font-bold tabular-nums text-gray-400 dark:text-gray-500">{{ $optPercent }}%</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>
                                @elseif ($qType === 'rating')
                                    <div class="flex items-center gap-1">
                                        @for ($star = 1; $star <= 5; $star++)
                                            <button
                                                wire:click="$parent.respondToSurvey({{ $comment->id }}, {{ $qIndex }}, {{ $star }})"
                                                class="transition-transform hover:scale-110"
                                            >
                                                <svg class="h-5 w-5 {{ $userAnswer && $star <= $userAnswer ? 'text-amber-400' : 'text-gray-300 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                </svg>
                                            </button>
                                        @endfor
                                        @if (count($qResponses) > 0)
                                            @php $avgRating = round(collect($qResponses)->avg(), 1); @endphp
                                            <span class="ml-2 text-[11px] font-medium text-gray-500 dark:text-gray-400">
                                                {{ $avgRating }}/5 ({{ count($qResponses) }})
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    {{-- Text response --}}
                                    @if ($userAnswer)
                                        <p class="text-xs text-gray-600 dark:text-gray-400 italic">
                                            {{ __('filament-comments::messages.comment_types.survey_your_answer') }}: {{ $userAnswer }}
                                        </p>
                                    @else
                                        <div class="flex gap-2" x-data="{ answer: '' }">
                                            <input type="text" x-model="answer"
                                                placeholder="{{ __('filament-comments::messages.comment_types.survey_text_placeholder') }}"
                                                class="flex-1 rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs text-gray-700 dark:border-gray-700 dark:bg-white/5 dark:text-gray-300" />
                                            <button
                                                @click="$wire.$parent.respondToSurvey({{ $comment->id }}, {{ $qIndex }}, answer); answer = ''"
                                                class="rounded-lg bg-primary-500 px-2.5 py-1.5 text-[11px] font-medium text-white transition-colors hover:bg-primary-600"
                                            >
                                                {{ __('filament-comments::messages.comment_types.survey_submit') }}
                                            </button>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @elseif ($comment->type === \Codenzia\FilamentComments\Enums\CommentType::Risk)
            {{-- Risk Rendering --}}
            @php
                $riskData = $comment->getDecodedComment();
                $riskTitle = $riskData['title'] ?? '';
                $riskCategory = $riskData['category'] ?? '';
                $riskLikelihood = $riskData['likelihood'] ?? '';
                $riskImpact = $riskData['impact'] ?? '';
                $riskMitigation = $riskData['mitigation_plan'] ?? '';
                $acknowledgedBy = $riskData['acknowledged_by'] ?? [];
                $isAcknowledged = in_array((string) auth()->id(), $acknowledgedBy, true);

                $likelihoodValues = ['rare' => 1, 'unlikely' => 2, 'possible' => 3, 'likely' => 4, 'almost_certain' => 5];
                $impactValues = ['negligible' => 1, 'minor' => 2, 'moderate' => 3, 'major' => 4, 'critical' => 5];
                $riskScore = ($likelihoodValues[$riskLikelihood] ?? 1) * ($impactValues[$riskImpact] ?? 1);

                $severityColor = match(true) {
                    $riskScore >= 17 => 'danger',
                    $riskScore >= 10 => 'warning',
                    $riskScore >= 5 => 'warning',
                    default => 'success',
                };
                $severityLabel = match(true) {
                    $riskScore >= 17 => __('filament-comments::messages.comment_types.risk_severity_critical'),
                    $riskScore >= 10 => __('filament-comments::messages.comment_types.risk_severity_high'),
                    $riskScore >= 5 => __('filament-comments::messages.comment_types.risk_severity_medium'),
                    default => __('filament-comments::messages.comment_types.risk_severity_low'),
                };

                $categoryIcons = [
                    'technical' => 'heroicon-o-cpu-chip',
                    'schedule' => 'heroicon-o-clock',
                    'budget' => 'heroicon-o-banknotes',
                    'resource' => 'heroicon-o-user-group',
                    'scope' => 'heroicon-o-arrows-pointing-out',
                    'security' => 'heroicon-o-shield-exclamation',
                    'other' => 'heroicon-o-ellipsis-horizontal-circle',
                ];
                $catIcon = $categoryIcons[$riskCategory] ?? 'heroicon-o-exclamation-triangle';
            @endphp
            <div class="mt-2 max-w-2xl">
                <div @class([
                    'rounded-xl border overflow-hidden',
                    'border-red-200 dark:border-red-800/50' => $severityColor === 'danger',
                    'border-amber-200 dark:border-amber-800/50' => $severityColor === 'warning',
                    'border-green-200 dark:border-green-800/50' => $severityColor === 'success',
                ])>
                    {{-- Header --}}
                    <div @class([
                        'flex items-center gap-3 px-4 py-2.5 border-b',
                        'bg-red-50 border-red-200 dark:bg-red-500/10 dark:border-red-800/50' => $severityColor === 'danger',
                        'bg-amber-50 border-amber-200 dark:bg-amber-500/10 dark:border-amber-800/50' => $severityColor === 'warning',
                        'bg-green-50 border-green-200 dark:bg-green-500/10 dark:border-green-800/50' => $severityColor === 'success',
                    ])>
                        <x-filament::icon :icon="$catIcon" @class([
                            'h-4.5 w-4.5',
                            'text-red-500 dark:text-red-400' => $severityColor === 'danger',
                            'text-amber-500 dark:text-amber-400' => $severityColor === 'warning',
                            'text-green-500 dark:text-green-400' => $severityColor === 'success',
                        ]) />
                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $riskTitle }}</h4>
                        </div>
                        <span @class([
                            'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase',
                            'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-300' => $severityColor === 'danger',
                            'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300' => $severityColor === 'warning',
                            'bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-300' => $severityColor === 'success',
                        ])>
                            {{ $severityLabel }} ({{ $riskScore }})
                        </span>
                    </div>

                    {{-- Body --}}
                    <div class="px-4 py-3 space-y-2">
                        <div class="flex flex-wrap gap-3 text-[11px]">
                            <div>
                                <span class="text-gray-400 dark:text-gray-500">{{ __('filament-comments::messages.comment_types.risk_category') }}:</span>
                                <span class="ml-1 font-medium text-gray-700 dark:text-gray-300 capitalize">{{ str_replace('_', ' ', $riskCategory) }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400 dark:text-gray-500">{{ __('filament-comments::messages.comment_types.risk_likelihood') }}:</span>
                                <span class="ml-1 font-medium text-gray-700 dark:text-gray-300 capitalize">{{ str_replace('_', ' ', $riskLikelihood) }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400 dark:text-gray-500">{{ __('filament-comments::messages.comment_types.risk_impact') }}:</span>
                                <span class="ml-1 font-medium text-gray-700 dark:text-gray-300 capitalize">{{ str_replace('_', ' ', $riskImpact) }}</span>
                            </div>
                        </div>

                        @if ($riskMitigation)
                            <div>
                                <span class="text-[11px] text-gray-400 dark:text-gray-500">{{ __('filament-comments::messages.comment_types.risk_mitigation') }}:</span>
                                <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-400">{{ $riskMitigation }}</p>
                            </div>
                        @endif

                        <div class="flex items-center justify-between pt-1">
                            <button
                                wire:click="$parent.acknowledgeRisk({{ $comment->id }})"
                                @class([
                                    'inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-[11px] font-medium transition-colors',
                                    'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300' => $isAcknowledged,
                                    'border-gray-200 text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:border-gray-700 dark:text-gray-400 dark:hover:border-gray-500' => !$isAcknowledged,
                                ])
                            >
                                @if ($isAcknowledged)
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14Zm3.28-8.72a.75.75 0 0 0-1.06-1.06L7 8.44 5.78 7.22a.75.75 0 0 0-1.06 1.06l1.75 1.75a.75.75 0 0 0 1.06 0l3.75-3.75Z" clip-rule="evenodd" />
                                    </svg>
                                @endif
                                {{ __('filament-comments::messages.comment_types.risk_acknowledge') }}
                            </button>
                            @if (count($acknowledgedBy) > 0)
                                <span class="text-[10px] text-gray-400 dark:text-gray-500">
                                    {{ trans_choice('filament-comments::messages.comment_types.risk_acknowledged_count', count($acknowledgedBy), ['count' => count($acknowledgedBy)]) }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @else
            @php
                $commentHtml = $comment->comment;
                // Render checklist items — interactive only if user can edit
                $checklistIndex = 0;
                $commentHtml = preg_replace_callback(
                    '/\[([ xX])\]/',
                    function ($matches) use (&$checklistIndex, $canEditChecklist) {
                        $idx = $checklistIndex++;
                        $checked = $matches[1] !== ' ';
                        $icon = $checked
                            ? '<svg class="h-4 w-4 text-primary-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>'
                            : '<svg class="h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="4" stroke-width="2"/></svg>';
                        if ($canEditChecklist) {
                            return '<button wire:click="toggleChecklist(' . $idx . ')" class="inline-flex items-center align-text-bottom cursor-pointer hover:opacity-80">' . $icon . '</button>';
                        }
                        return '<span class="inline-flex items-center align-text-bottom">' . $icon . '</span>';
                    },
                    $commentHtml
                );
            @endphp
            <div class="comment-body prose prose-sm mt-1 max-w-none text-gray-700 dark:prose-invert dark:text-gray-300"
                x-data x-init="$nextTick(() => {
                    window.__mentionPopoverManager && window.__mentionPopoverManager.bind($el, @js($mentionables));
                    @if(config('filament-comments.code_highlighting', true))
                        $el.querySelectorAll('pre code:not(.hljs)').forEach(el => typeof hljs !== 'undefined' && hljs.highlightElement(el));
                    @endif
                    $el.querySelectorAll('pre').forEach(pre => {
                        pre.classList.add('comment-code-block');
                        pre.style.position = 'relative';
                        const copyIcon = `<svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='currentColor' style='width:16px;height:16px'><path stroke-linecap='round' stroke-linejoin='round' d='M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9.334a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184' /></svg>`;
                        const btn = document.createElement('button');
                        btn.className = 'code-copy-btn';
                        btn.innerHTML = copyIcon;
                        btn.addEventListener('click', () => {
                            const code = pre.querySelector('code');
                            navigator.clipboard.writeText(code ? code.textContent : pre.textContent);
                            btn.innerHTML = `<svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='currentColor' style='width:16px;height:16px'><path stroke-linecap='round' stroke-linejoin='round' d='m4.5 12.75 6 6 9-13.5' /></svg>`;
                            btn.classList.add('copied');
                            setTimeout(() => { btn.innerHTML = copyIcon; btn.classList.remove('copied'); }, 2000);
                        })
                        pre.appendChild(btn);
                    });
                })">
                {!! $commentHtml !!}
            </div>

            {{-- Link Preview Cards --}}
            @if (! empty($comment->link_previews))
                <div class="mt-2 space-y-2">
                    @foreach ($comment->link_previews as $preview)
                        <a href="{{ $preview['url'] }}" target="_blank" rel="noopener"
                            class="flex gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3 no-underline transition-colors hover:bg-gray-100 dark:border-white/10 dark:bg-white/5 dark:hover:bg-white/[0.08]">
                            @if (! empty($preview['image']))
                                <div class="hidden sm:block shrink-0">
                                    <img src="{{ $preview['image'] }}" alt=""
                                        class="h-16 w-24 rounded-md object-cover"
                                        loading="lazy"
                                        onerror="this.parentElement.style.display='none'">
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                @if (! empty($preview['title']))
                                    <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $preview['title'] }}
                                    </p>
                                @endif
                                @if (! empty($preview['description']))
                                    <p class="mt-0.5 line-clamp-2 text-xs text-gray-500 dark:text-gray-400">
                                        {{ \Illuminate\Support\Str::limit($preview['description'], 150) }}
                                    </p>
                                @endif
                                <p class="mt-1 text-[10px] text-gray-400 dark:text-gray-500">
                                    {{ $preview['domain'] ?? parse_url($preview['url'], PHP_URL_HOST) }}
                                </p>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Linked Task Card --}}
            @if ($comment->linked_task_id && $comment->linkedTask)
                @php $linkedTask = $comment->linkedTask; @endphp
                <div class="mt-2">
                    <a href="{{ url(config('filament-comments.task_mentionable.url', 'admin/tasks/{id}')) }}"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs no-underline transition-colors hover:bg-gray-100 dark:border-white/10 dark:bg-white/5 dark:hover:bg-white/[0.08]"
                        onclick="event.preventDefault(); window.location.href=this.href.replace('{id}', '{{ $linkedTask->id }}')">
                        <x-filament::icon icon="heroicon-o-clipboard-document-check" class="h-4 w-4 text-primary-500" />
                        <span class="font-medium text-gray-900 dark:text-white">{{ $linkedTask->{config('filament-comments.task_mentionable.column.label', 'title')} }}</span>
                    </a>
                </div>
            @endif
        @endif

        {{-- Footer: Reactions + Reply + Replies Toggle --}}
        @php
            $reactions = $comment->getReactionsSummary();
            $userReaction = $comment->userReaction();
            $reactionTypes = config('filament-comments.reactions', []);
            $hasAnyReactions = collect($reactions)->sum() > 0;
        @endphp

        <div class="mt-2 flex flex-wrap items-center gap-2">
            {{-- Reaction Picker --}}
            @if ($canPost)
                <div class="relative" x-data="{ open: @entangle('showReactionPicker') }">
                    <button wire:click="toggleReactionPicker"
                        class="inline-flex items-center justify-center rounded-full p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/5 dark:hover:text-gray-300"
                        title="Add reaction">
                        <x-filament::icon icon="heroicon-o-face-smile" class="h-4 w-4" />
                    </button>

                    {{-- Picker Popover --}}
                    <div x-show="open" x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                        @click.away="open = false"
                        class="absolute bottom-full left-0 z-50 mb-2 flex items-center gap-0.5 rounded-full bg-white px-2 py-1.5 shadow-lg ring-1 ring-gray-200/80 dark:bg-gray-800 dark:ring-gray-700">
                        @foreach ($reactionTypes as $type => $emoji)
                            <button wire:click="toggleReaction('{{ $type }}')"
                                class="rounded-full p-1 text-lg transition-transform duration-100 hover:scale-125 hover:bg-gray-100 dark:hover:bg-gray-700"
                                title="{{ __('filament-comments::messages.reactions.' . $type) }}">
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
                        if ($count <= 0) {
                            continue;
                        }
                        $isActive = $userReaction && $userReaction->reaction_type === $type;
                    @endphp
                    <button
                        @if ($canPost) wire:click="toggleReaction('{{ $type }}')" @endif
                        @class([
                            'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium transition-all duration-150',
                            'bg-primary-50 text-primary-700 ring-1 ring-primary-200 dark:bg-primary-500/10 dark:text-primary-300 dark:ring-primary-500/30' => $isActive,
                            'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-white/5 dark:text-gray-400 dark:hover:bg-white/10' => !$isActive,
                            'cursor-default' => !$canPost,
                        ])
                        title="{{ __('filament-comments::messages.reactions.' . $type) }}">
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
                <button wire:click="toggleReplyForm"
                    class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-xs font-medium text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-300">
                    <x-filament::icon icon="heroicon-o-chat-bubble-left" class="h-3.5 w-3.5" />
                    {{ __('filament-comments::messages.comments.reply') }}
                </button>
            @endif

            {{-- Replies Toggle --}}
            @if ($comment->replies->count() > 0)
                <button wire:click="toggleReplies"
                    class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-xs font-medium text-primary-600 transition-colors hover:bg-primary-50 dark:text-primary-400 dark:hover:bg-white/5">
                    <x-filament::icon :icon="$showReplies ? 'heroicon-o-chevron-up' : 'heroicon-o-chevron-down'" class="h-3.5 w-3.5" />
                    {{ trans_choice('filament-comments::messages.comments.replies_count', $comment->replies->count(), ['count' => $comment->replies->count()]) }}
                </button>
            @endif
        </div>

        {{-- Reply Form --}}
        @if ($showReplyForm)
            <div class="mt-3 border-l-2 border-primary-200 pl-4 dark:border-primary-500/30" x-data="{ uploading: false }"
                x-on:livewire-upload-start="uploading = true"
                x-on:livewire-upload-finish="uploading = false; if ($refs.replyImageInput{{ $comment->id }}) $refs.replyImageInput{{ $comment->id }}.value = ''; if ($refs.replyFileInput{{ $comment->id }}) $refs.replyFileInput{{ $comment->id }}.value = ''"
                x-on:livewire-upload-error="uploading = false; if ($refs.replyImageInput{{ $comment->id }}) $refs.replyImageInput{{ $comment->id }}.value = ''; if ($refs.replyFileInput{{ $comment->id }}) $refs.replyFileInput{{ $comment->id }}.value = ''">
                {{-- Hidden file input for image upload --}}
                <input type="file" wire:model="tempImages" accept="image/*" multiple class="hidden"
                    x-ref="replyImageInput{{ $comment->id }}" />

                {{-- Hidden file input for document upload --}}
                <input type="file" wire:model="tempFiles" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.ppt,.pptx"
                    multiple class="hidden" x-ref="replyFileInput{{ $comment->id }}" />

                <div class="comment-composer reply-composer-{{ $comment->id }} rounded-xl dark:bg-gray-900">
                    <div class="comment-composer__editor">
                        {{ $this->replyForm }}
                    </div>

                    {{-- Bottom toolbar --}}
                    <div
                        class="relative flex items-center gap-0.5 border-t border-gray-700 dark:border-gray-700 px-2 py-1.5">
                        <div class="relative flex items-center gap-0.5">
                            {{-- @ mention --}}
                            <button
                                class="flex items-center justify-center rounded-md p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300"
                                title="{{ __('filament-comments::messages.comments.mention_hint') }}"
                                onclick="window.__triggerMention(this.closest('.comment-composer'))">
                                <x-filament::icon icon="heroicon-o-at-symbol" class="h-4.5 w-4.5" />
                            </button>

                            {{-- Image shortcut --}}
                            <button @click="$refs.replyImageInput{{ $comment->id }}.click()"
                                class="flex items-center justify-center rounded-md p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300"
                                title="{{ __('filament-comments::messages.comment_types.image') }}">
                                <x-filament::icon icon="heroicon-o-photo" class="h-4.5 w-4.5" />
                            </button>

                            {{-- File upload --}}
                            <button @click="$refs.replyFileInput{{ $comment->id }}.click()"
                                class="flex items-center justify-center rounded-md p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300"
                                title="{{ __('filament-comments::messages.comment_types.file') ?? 'Attach file' }}">
                                <x-filament::icon icon="heroicon-o-paper-clip" class="h-4.5 w-4.5" />
                            </button>

                            {{-- Emoji picker --}}
                            <div class="relative" x-data="{ emojiOpen: false }">
                                <button @click="emojiOpen = !emojiOpen"
                                    class="flex items-center justify-center rounded-md p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300"
                                    title="{{ __('filament-comments::messages.comments.emoji') ?? 'Emoji' }}">
                                    <x-filament::icon icon="heroicon-o-face-smile" class="h-4.5 w-4.5" />
                                </button>
                                <div x-show="emojiOpen" x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="opacity-100 scale-100"
                                    x-transition:leave-end="opacity-0 scale-95" @click.away="emojiOpen = false"
                                    class="absolute left-0 z-50 mb-2 bottom-full w-64 max-h-48 overflow-y-auto rounded-lg bg-white p-2 shadow-lg ring-1 ring-gray-200/80 dark:bg-gray-900 dark:ring-gray-700">
                                    <div class="grid grid-cols-8 gap-0.5">
                                        @foreach (['😀', '😂', '😊', '😍', '🥰', '😎', '🤔', '😏', '😢', '😭', '😡', '🤯', '🥳', '😴', '🤗', '😈', '👍', '👎', '👏', '🙌', '🤝', '✌️', '🔥', '❤️', '💯', '⭐', '🎉', '✅', '❌', '💡', '🚀', '👀', '💬', '📌', '🏆', '💪'] as $emoji)
                                            <button type="button"
                                                @click="window.__insertEmoji($el.closest('.comment-composer'), '{{ $emoji }}'); emojiOpen = false"
                                                class="flex items-center justify-center rounded p-1 text-lg transition-transform duration-100 hover:scale-125 hover:bg-gray-100 dark:hover:bg-white/10">
                                                {{ $emoji }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex-1"></div>

                        {{-- Upload indicator --}}
                        <div x-show="uploading" x-cloak
                            class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500">
                            <x-filament::loading-indicator class="h-3.5 w-3.5" />
                            {{ __('uploading') }}
                        </div>

                        {{-- Cancel button --}}
                        <button wire:click="toggleReplyForm"
                            class="flex items-center justify-center rounded-lg p-1.5 text-gray-400 transition-colors hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300"
                            title="{{ __('filament-comments::messages.comments.cancel') }}">
                            <x-filament::icon icon="heroicon-o-x-mark" class="h-4 w-4" />
                        </button>

                        {{-- Send button --}}
                        <button wire:click="reply" wire:loading.attr="disabled" :disabled="uploading"
                            class="flex items-center justify-center rounded-lg dark:hover:bg-gray-800 p-1.5 disabled:opacity-50">
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
                    <livewire:filament-comments::comment-item :key="'reply-' . $reply->id" :comment="$reply" :mentionables="$mentionables"
                        :channelMentionables="$channelMentionables" />
                @endforeach
            </div>
        @endif
    </div>

    <x-filament-actions::modals />
</div>

@script
    <script>
        if (!window.__mentionPopoverManager) {
            window.__mentionPopoverManager = (function() {
                let popoverEl = null;
                let hideTimeout = null;

                function getPopover() {
                    if (popoverEl) return popoverEl;

                    popoverEl = document.createElement('div');
                    popoverEl.className =
                        'fixed z-[9999] w-64 rounded-xl bg-white shadow-xl ring-1 ring-gray-200/80 dark:bg-gray-800  transition-all duration-150';
                    popoverEl.style.cssText =
                        'pointer-events:auto;opacity:0;transform:translateY(4px) scale(0.95);display:none;';
                    popoverEl.innerHTML = `
                <a id="mp-link" href="#" class="block p-4 no-underline transition-colors hover:bg-gray-50 rounded-xl dark:hover:bg-white/5">
                    <div class="flex items-center gap-3">
                        <div id="mp-avatar-wrap" class="shrink-0" style="display:none;">
                            <img id="mp-avatar" src="" alt="" class="h-10 w-10 rounded-full object-cover ring-2 ring-white ">
                        </div>
                        <div id="mp-initials-wrap" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-50 ring-2 ring-white  dark:bg-primary-500/10" style="display:none;">
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
                    setTimeout(() => {
                        if (popoverEl) popoverEl.style.display = 'none';
                    }, 150);
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
                            const mentionText = (link.textContent || '').replace(/^[^a-zA-Z0-9\s]/,
                                '').trim();
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

                return {
                    bind
                };
            })();
        }
    </script>
@endscript
