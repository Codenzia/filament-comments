<?php

namespace Codenzia\FilamentComments\Livewire;

use Codenzia\FilamentComments\Enums\CommentType;
use Codenzia\FilamentComments\Events\UserMentioned;
use Codenzia\FilamentComments\Filament\Pages\DiscussionPage;
use Codenzia\FilamentComments\Forms\TributeTextarea;
use Codenzia\FilamentComments\Models\Comment;
use Codenzia\FilamentComments\Models\CommentBookmark;
use Codenzia\FilamentComments\Models\CommentChannel;
use Codenzia\FilamentComments\Traits\ExtractsMentions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class CommentItem extends Component implements HasActions, HasForms
{
    use ExtractsMentions;
    use InteractsWithActions;
    use InteractsWithForms;
    use WithFileUploads;

    public Comment $comment;

    public bool $showReplyForm = false;

    public bool $showEditForm = false;

    public bool $showReplies = false;

    public bool $showReactionPicker = false;

    public ?array $replyData = [];

    public ?array $editData = [];

    public array $mentionables = [];

    public array $channelMentionables = [];

    /** @var array<TemporaryUploadedFile> */
    public $tempImages = [];

    /** @var array<TemporaryUploadedFile> */
    public $tempFiles = [];

    protected $listeners = [
        'reactionUpdated' => '$refresh',
        'commentDeleted' => '$refresh',
        'joinedChannel' => '$refresh',
        'voteUpdated' => 'refreshComment',
        'eventResponseUpdated' => 'refreshComment',
    ];

    public function mount(array $mentionables = [], array $channelMentionables = []): void
    {
        $this->replyForm->fill();

        // Only fill the edit form for text-type comments (or legacy null type).
        // Vote/image types store JSON that is not compatible with the TributeTextarea editor.
        if ($this->comment->type === null || $this->comment->type === CommentType::Text) {
            $this->editForm->fill([
                'comment' => $this->comment->comment,
            ]);
        }

        $this->mentionables = $mentionables;
        $this->channelMentionables = $channelMentionables;
        // Ensure reactions are loaded
        $this->comment->load('reactions');
    }

    public function updatedTempImages(): void
    {
        $this->validate([
            'tempImages.*' => 'image|max:5120',
        ]);

        $urls = [];

        foreach ($this->tempImages as $image) {
            $path = $image->store('comment-images', 'public');
            $urls[] = Storage::disk('public')->url($path);
        }

        $this->tempImages = [];

        $commentId = $this->comment->id;
        $urlsJson = json_encode($urls);
        $this->js("window.__insertReplyImages({$commentId}, {$urlsJson})");
    }

    public function updatedTempFiles(): void
    {
        $this->validate([
            'tempFiles.*' => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,csv,txt,ppt,pptx',
        ]);

        $files = [];

        foreach ($this->tempFiles as $file) {
            $originalName = $file->getClientOriginalName();
            $path = $file->storeAs('comment-files', $originalName, 'public');
            $files[] = [
                'url' => Storage::disk('public')->url($path),
                'name' => $originalName,
                'extension' => strtolower($file->getClientOriginalExtension()),
            ];
        }

        $this->tempFiles = [];

        $commentId = $this->comment->id;
        $filesJson = json_encode($files);
        $this->js("window.__insertReplyFiles({$commentId}, {$filesJson})");
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
                    ->placeholder(config('filament-comments.editor.placeholder', '')),
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
                    ->placeholder(config('filament-comments.editor.placeholder', '')),
            ])
            ->statePath('editData');
    }

    public function refreshComment(): void
    {
        $this->comment->refresh();
    }

    public function toggleReplyForm(): void
    {
        $this->showReplyForm = ! $this->showReplyForm;
    }

    public function toggleEditForm(): void
    {
        // Only allow editing text-type comments (vote/image types are not editable via the text editor)
        if ($this->comment->type !== null && $this->comment->type !== CommentType::Text) {
            return;
        }

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
        if (auth()->id() !== $this->comment->user_id) {
            Notification::make()
                ->title(__('filament-comments::messages.notifications.unauthorized'))
                ->danger()
                ->send();

            return;
        }

        $data = $this->editForm->getState();

        $this->comment->update([
            'comment' => $data['comment'],
        ]);

        Notification::make()
            ->title(__('filament-comments::messages.notifications.comment_updated'))
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
                ->title(__('filament-comments::messages.notifications.unauthorized'))
                ->body(__('filament-comments::messages.notifications.unauthorized_members_only'))
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
            'type' => $this->comment->type,
        ]);

        // Detect mentions in the reply and send notifications
        $mentionedNames = $this->extractMentions($data['comment']);
        if (! empty($mentionedNames)) {
            $userModel = $this->getUserModelClass();
            $columnName = config('filament-comments.mentionable.column.label', 'name');

            foreach ($mentionedNames as $name) {
                $mentionedUser = $userModel::where($columnName, $name)->first();
                if ($mentionedUser && $mentionedUser->id !== auth()->id()) {
                    event(new UserMentioned($mentionedUser, $reply->comment, auth()->user()));
                }
            }
        }

        Notification::make()
            ->title(__('filament-comments::messages.notifications.reply_created'))
            ->success()
            ->send();

        $this->showReplyForm = false;
        $this->replyForm->fill();
        $this->tempImages = [];
        $this->tempFiles = [];
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

    /**
     * Check if the current user is a member of the commentable model (project, task, etc.)
     * or the comment author. Used for checklist editing permissions.
     */
    public function canEditChecklist(): bool
    {
        // Comment author can always edit their own checklists
        if (auth()->id() === $this->comment->user_id) {
            return true;
        }

        // For channel-based comments, check channel membership
        if ($this->comment->channel) {
            return $this->canUserPostInChannel();
        }

        // For commentable-based comments (project, task, etc.), check if the model
        // has a members() relationship and the user is a member
        $commentable = $this->comment->commentable;

        if ($commentable && method_exists($commentable, 'members')) {
            return $commentable->members()->where('users.id', auth()->id())->exists();
        }

        // Fallback: only the comment author (already handled above)
        return false;
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

    // ── Pin ────────────────────────────────────────────────────────

    public function pinComment(): void
    {
        $this->comment->pin();

        Notification::make()
            ->title(__('filament-comments::messages.notifications.comment_pinned'))
            ->success()
            ->send();

        $this->dispatch('commentPinned');
    }

    public function unpinComment(): void
    {
        $this->comment->unpin();

        Notification::make()
            ->title(__('filament-comments::messages.notifications.comment_unpinned'))
            ->success()
            ->send();

        $this->dispatch('commentPinned');
    }

    // ── Resolve ──────────────────────────────────────────────────

    public function resolveThread(): void
    {
        if ($this->comment->isReply()) {
            return;
        }

        $this->comment->resolve();

        Notification::make()
            ->title(__('filament-comments::messages.notifications.thread_resolved'))
            ->success()
            ->send();

        $this->dispatch('commentResolved');
    }

    public function unresolveThread(): void
    {
        $this->comment->unresolve();

        Notification::make()
            ->title(__('filament-comments::messages.notifications.thread_unresolved'))
            ->success()
            ->send();

        $this->dispatch('commentResolved');
    }

    // ── Bookmark ─────────────────────────────────────────────────

    public function toggleBookmark(): void
    {
        $existing = CommentBookmark::where('user_id', auth()->id())
            ->where('comment_id', $this->comment->id)
            ->first();

        if ($existing) {
            $existing->delete();

            Notification::make()
                ->title(__('filament-comments::messages.notifications.bookmark_removed'))
                ->success()
                ->send();
        } else {
            CommentBookmark::create([
                'user_id' => auth()->id(),
                'comment_id' => $this->comment->id,
            ]);

            Notification::make()
                ->title(__('filament-comments::messages.notifications.bookmark_added'))
                ->success()
                ->send();
        }

        $this->comment->refresh();
    }

    // ── Direct Message ──────────────────────────────────────────

    public function startDirectMessage(int $userId): void
    {
        $channel = CommentChannel::findOrCreateDirectMessage([$userId]);

        $this->redirect(DiscussionPage::getUrl(['record' => $channel->id]));
    }

    // ── Checklist Toggle ─────────────────────────────────────────

    public function toggleChecklist(int $index): void
    {
        if (! $this->canEditChecklist()) {
            return;
        }

        $body = $this->comment->comment;

        $counter = 0;
        $body = preg_replace_callback(
            '/\[([ xX])\]/',
            function ($matches) use ($index, &$counter) {
                if ($counter++ === $index) {
                    return $matches[1] === ' ' ? '[x]' : '[ ]';
                }

                return $matches[0];
            },
            $body
        );

        $this->comment->update(['comment' => $body]);
        $this->comment->refresh();
    }

    public function delete(): void
    {
        if (! $this->comment) {
            return;
        }

        // Check if user can delete (either owner or has permission)
        if (auth()->id() !== $this->comment->user_id && ! auth()->user()->can('delete', $this->comment)) {
            Notification::make()
                ->title(__('filament-comments::messages.notifications.unauthorized'))
                ->danger()
                ->send();

            return;
        }

        $this->comment->delete();

        Notification::make()
            ->title(__('filament-comments::messages.notifications.deleted'))
            ->success()
            ->send();

        $this->dispatch('commentDeleted');
    }

    public function render(): View
    {
        $canPost = $this->canUserPostInChannel();

        return view('filament-comments::livewire.comment-item', [
            'canPost' => $canPost,
            'canEditChecklist' => $this->canEditChecklist(),
            'isBookmarked' => $this->comment->isBookmarkedBy(),
        ]);
    }
}
