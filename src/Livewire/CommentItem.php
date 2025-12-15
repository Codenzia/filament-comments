<?php

namespace Codenzia\FilamentComments\Livewire;

use Codenzia\FilamentComments\Models\Comment;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CommentItem extends Component
{
    public Comment $comment;

    public function delete(): void
    {
        if (! $this->comment) {
            return;
        }

        // Check if user can delete (either owner or has permission)
        if (auth()->id() !== $this->comment->user_id && ! auth()->user()->can('delete', $this->comment)) {
            Notification::make()
                ->title(__('codenzia-comments::codenzia-comments.notifications.unauthorized'))
                ->danger()
                ->send();

            return;
        }

        $this->comment->delete();

        Notification::make()
            ->title(__('codenzia-comments::codenzia-comments.notifications.deleted'))
            ->success()
            ->send();

        $this->dispatch('commentDeleted');
    }

    public function render(): View
    {
        return view('codenzia-comments::livewire.comment-item');
    }
}
