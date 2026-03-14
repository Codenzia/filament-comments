<?php

namespace Codenzia\FilamentComments\Livewire;

use Codenzia\FilamentComments\Traits\HasComments;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

/**
 * QuickComments — A lightweight, embeddable comment preview + composer.
 *
 * Designed for modals, sidebars, and cards where the full CommentsComponent
 * would be too heavy or cause Livewire nesting issues. Uses Alpine.js
 * optimistic UI so new comments appear instantly without re-rendering.
 *
 * Usage:
 *   <livewire:filament-comments::quick-comments :record="$task" />
 *   — or via the Blade component wrapper —
 *   <x-filament-comments::quick-comments :record="$task" />
 */
class QuickComments extends Component
{
    public Model $record;

    public int $limit = 3;

    public ?string $viewAllUrl = null;

    public bool $transparent = false;

    public function mount(Model $record, int $limit = 3, ?string $viewAllUrl = null, bool $transparent = false): void
    {
        $this->record = $record;
        $this->limit = $limit;
        $this->viewAllUrl = $viewAllUrl;
        $this->transparent = $transparent;
    }

    public function postQuickComment(string $comment): void
    {
        $comment = trim($comment);

        if (empty($comment)) {
            return;
        }

        /** @var Model&HasComments $record */
        $record = $this->record;
        $record->comment($comment);

        Notification::make()
            ->title(__('Comment posted'))
            ->success()
            ->send();
    }

    public function render(): View
    {
        $comments = $this->record->comments()
            ->with('commentator')
            ->latest()
            ->limit($this->limit)
            ->get();

        $commentsCount = $this->record->comments()->count();

        $currentUser = auth()->user();
        $currentUserAvatar = ($currentUser && method_exists($currentUser, 'getFilamentAvatarUrl'))
            ? $currentUser->getFilamentAvatarUrl()
            : null;
        $currentUserInitial = mb_substr($currentUser?->name ?? '?', 0, 1);

        return view('filament-comments::livewire.quick-comments', [
            'comments' => $comments,
            'commentsCount' => $commentsCount,
            'currentUser' => $currentUser,
            'currentUserAvatar' => $currentUserAvatar,
            'currentUserInitial' => $currentUserInitial,
        ]);
    }
}
