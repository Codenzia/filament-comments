<?php

namespace Codenzia\FilamentComments\Livewire;

use Codenzia\FilamentComments\Events\UserMentioned;
use Codenzia\FilamentComments\Forms\TributeTextarea;
use Codenzia\FilamentComments\Models\Comment;
use Codenzia\FilamentComments\Traits\ExtractsMentions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CommentItem extends Component implements HasActions, HasForms
{
    use ExtractsMentions;
    use InteractsWithActions;
    use InteractsWithForms;

    public Comment $comment;

    public bool $showReplyForm = false;

    public bool $showEditForm = false;

    public bool $showReplies = false;

    public bool $showReactionPicker = false;

    public ?array $replyData = [];

    public ?array $editData = [];

    public array $mentionables = [];

    public array $channelMentionables = [];

    protected $listeners = [
        'reactionUpdated' => '$refresh',
        'commentDeleted' => '$refresh',
        'joinedChannel' => '$refresh',
    ];

    public function mount(array $mentionables = [], array $channelMentionables = []): void
    {
        $this->replyForm->fill();
        $this->editForm->fill([
            'comment' => $this->comment->comment,
        ]);
        $this->mentionables = $mentionables;
        $this->channelMentionables = $channelMentionables;
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
                    ->urlPattern('/users/{id}/profile')
                    ->mentionables($this->mentionables)
                    ->channelMentionables($this->channelMentionables)
                    ->placeholder(config('codenzia-comments.editor.placeholder', '')),
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
                    ->channelMentionables($this->channelMentionables)
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

    public function toggleReplies(): void
    {
        $this->showReplies = ! $this->showReplies;
    }

    public function toggleReactionPicker(): void
    {
        $this->showReactionPicker = ! $this->showReactionPicker;
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
        if (! $this->canUserPostInChannel()) {
            Notification::make()
                ->title('Unauthorized')
                ->body('Only members can post in this channel.')
                ->danger()
                ->send();

            return;
        }

        $data = $this->replyForm->getState();

        $reply = $this->comment->replies()->create([
            'comment' => $data['comment'],
            'user_id' => auth()->id(),
            'commentable_id' => $this->comment->commentable_id,
            'commentable_type' => $this->comment->commentable_type,
            'channel_id' => $this->comment->channel_id,
        ]);

        // Detect mentions in the reply and send notifications
        $mentionedNames = $this->extractMentions($data['comment']);
        if (! empty($mentionedNames)) {
            $userModel = $this->getUserModelClass();
            $columnName = config('codenzia-comments.mentionable.column.label', 'name');

            foreach ($mentionedNames as $name) {
                $mentionedUser = $userModel::where($columnName, $name)->first();
                if ($mentionedUser && $mentionedUser->id !== auth()->id()) {
                    event(new UserMentioned($mentionedUser, $reply->comment, auth()->user()));
                }
            }
        }

        Notification::make()
            ->title(__('codenzia-comments::codenzia-comments.notifications.reply_created'))
            ->success()
            ->send();

        $this->showReplyForm = false;
        $this->replyForm->fill();
        $this->dispatch('commentDeleted'); // Refresh parent
    }

    public function canUserPostInChannel(): bool
    {
        $channel = $this->comment->channel;

        if (! $channel) {
            return true;
        }

        return $channel->members()->where('user_id', auth()->id())->exists();
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

        // Close the reaction picker
        $this->showReactionPicker = false;

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
        return view('codenzia-comments::livewire.comment-item', [
            'canPost' => $this->canUserPostInChannel(),
        ]);
    }
}
