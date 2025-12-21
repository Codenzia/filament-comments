<?php

namespace Codenzia\FilamentComments\Livewire;

use Codenzia\FilamentComments\Models\Comment;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Codenzia\FilamentComments\Forms\TributeTextarea;
use Illuminate\Support\Arr;

class CommentItem extends Component implements HasForms
{
    use InteractsWithForms;

    public Comment $comment;

    public bool $showReplyForm = false;

    public bool $showEditForm = false;

    public ?array $replyData = [];

    public ?array $editData = [];

    public array $mentionables = [];

    protected $listeners = ['reactionUpdated' => '$refresh', 'commentDeleted' => '$refresh'];

    public function mount(array $mentionables = []): void
    {
        $this->replyForm->fill();
        $this->editForm->fill([
            'comment' => $this->comment->comment,
        ]);
        $this->mentionables = collect($mentionables)->map(function ($user) {
            $name = is_array($user) ? Arr::get($user, 'name') : $user->name;
            return ['key' => $name, 'value' => $name];
        })->toArray();        
        // Ensure reactions are loaded
        $this->comment->load('reactions');
    }

    public function replyForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                TributeTextarea::make('comment')
                    ->hiddenLabel()
                    ->required()
                    ->required()
                    ->urlPattern('/users/{id}/profile')
                    ->mentionables($this->mentionables)
                    ->placeholder(config('codenzia-comments.editor.placeholder', ''))                    
            ])
            ->statePath('replyData');
    }

    public function editForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                TributeTextarea::make('comment')
                    ->hiddenLabel()
                    ->required()
                    ->urlPattern('/users/{id}/profile')
                    ->mentionables($this->mentionables)
                    ->placeholder(config('codenzia-comments.editor.placeholder', '')),
            ])
            ->statePath('editData');
    }

    public function toggleReplyForm(): void
    {
        $this->showReplyForm = ! $this->showReplyForm;
    }

    public function toggleEditForm(): void
    {
        $this->showEditForm = ! $this->showEditForm;
        if ($this->showEditForm) {
            $this->editForm->fill([
                'comment' => $this->comment->comment,
            ]);
        }
    }

    public function edit(): void
    {
        $this->toggleEditForm();
    }

    public function updateComment(): void
    {
        $data = $this->editForm->getState();

        $this->comment->update([
            'comment' => $data['comment'],
        ]);

        Notification::make()
            ->title(__('codenzia-comments::codenzia-comments.notifications.comment_updated'))
            ->success()
            ->send();

        $this->showEditForm = false;
        $this->comment->refresh();
        $this->dispatch('commentDeleted'); // Refresh parent
    }

    public function reply(): void
    {
        $data = $this->replyForm->getState();

        $this->comment->replies()->create([
            'comment' => $data['comment'],
            'user_id' => auth()->id(),
            'commentable_id' => $this->comment->commentable_id,
            'commentable_type' => $this->comment->commentable_type,
        ]);

        Notification::make()
            ->title(__('codenzia-comments::codenzia-comments.notifications.reply_created'))
            ->success()
            ->send();

        $this->showReplyForm = false;
        $this->replyForm->fill();
        $this->dispatch('commentDeleted'); // Refresh parent
    }

    public function toggleReaction(string $reactionType): void
    {
        $userReaction = $this->comment->userReaction();

        if ($userReaction) {
            // If same reaction, remove it
            if ($userReaction->reaction_type === $reactionType) {
                $userReaction->delete();
            } else {
                // Change to new reaction
                $userReaction->update(['reaction_type' => $reactionType]);
            }
        } else {
            // Add new reaction
            $this->comment->reactions()->create([
                'user_id' => auth()->id(),
                'reaction_type' => $reactionType,
            ]);
        }

        // Refresh the comment to get updated reactions
        $this->comment->refresh();
        $this->dispatch('reactionUpdated');
    }

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
