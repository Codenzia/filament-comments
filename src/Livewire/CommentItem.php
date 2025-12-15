<?php

namespace Codenzia\FilamentComments\Livewire;

use Codenzia\FilamentComments\Models\Comment;
use Codenzia\FilamentComments\Models\CommentReaction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CommentItem extends Component implements HasForms
{
    use InteractsWithForms;

    public Comment $comment;
    public bool $showReplyForm = false;
    public ?array $replyData = [];

    protected $listeners = ['reactionUpdated' => '$refresh', 'commentDeleted' => '$refresh'];

    public function mount(): void
    {
        $this->replyForm->fill();
        // Ensure reactions are loaded
        $this->comment->load('reactions');
    }

    public function replyForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                RichEditor::make('comment')
                    ->hiddenLabel()
                    ->required()
                    ->placeholder(__('codenzia-comments::codenzia-comments.comments.reply_placeholder'))
                    ->extraInputAttributes(['style' => 'min-height: 4rem'])
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'underline',
                        'strike',
                        'bulletList',
                        'codeBlock',
                    ]),
            ])
            ->statePath('replyData');
    }

    public function toggleReplyForm(): void
    {
        $this->showReplyForm = ! $this->showReplyForm;
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
