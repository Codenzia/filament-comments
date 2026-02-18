<?php

namespace Codenzia\FilamentComments\Livewire;

use Codenzia\FilamentComments\Enums\CommentType;
use Codenzia\FilamentComments\Events\UserMentioned;
use Codenzia\FilamentComments\Forms\TributeTextarea;
use Codenzia\FilamentComments\Models\Comment;
use Codenzia\FilamentComments\Models\CommentChannel;
use Codenzia\FilamentComments\Traits\ExtractsMentions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class CommentsComponent extends Component implements HasActions, HasForms
{
    use ExtractsMentions;
    use InteractsWithActions;
    use InteractsWithForms;
    use WithFileUploads;

    public ?array $data = [];

    public ?array $voteData = [];

    public ?array $eventData = [];

    public string $commentType = 'text';

    /** @var array<\Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public $tempImages = [];

    /** @var array<\Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public $tempFiles = [];

    public Model $record;

    public array $mentionables = [];

    public ?int $activeChannelId = null;

    protected $listeners = ['commentDeleted' => '$refresh', 'reactionUpdated' => '$refresh'];

    public function mount(Model $record, array $mentionables = [], ?int $activeChannelId = null): void
    {
        $this->record = $record;

        $availableChannels = $this->getAvailableChannels();

        $this->activeChannelId = $activeChannelId
            ?? $availableChannels->first()?->id
            ?? $availableChannels->first()?->id;

        if (empty($mentionables)) {
            $userModel = config('codenzia-comments.mentionable.model');
            if ($userModel && class_exists($userModel)) {
                $mentionables = $userModel::all();
            }
        }

        $labelColumn = config('codenzia-comments.mentionable.column.label', 'name');
        $emailColumn = config('codenzia-comments.mentionable.column.email', 'email');
        $avatarColumn = config('codenzia-comments.mentionable.column.avatar', 'avatar');
        $idColumn = config('codenzia-comments.mentionable.column.id', 'id');

        $this->mentionables = collect($mentionables)->map(function ($user) use ($labelColumn, $emailColumn, $avatarColumn, $idColumn) {
            $name = is_array($user) ? Arr::get($user, $labelColumn) : ($user->{$labelColumn} ?? null);
            $email = is_array($user) ? Arr::get($user, $emailColumn) : ($user->{$emailColumn} ?? null);
            $avatarPath = is_array($user) ? Arr::get($user, $avatarColumn) : ($user->{$avatarColumn} ?? null);
            $id = is_array($user) ? Arr::get($user, $idColumn) : ($user->{$idColumn} ?? null);

            // Get full avatar URL or generate UI Avatars URL
            $avatar = $this->getAvatarUrl($avatarPath, $name);

            $urlPattern = config('codenzia-comments.mentionable.url', 'admin/users/{id}');
            $link = str_replace('{id}', $id, $urlPattern);

            return [
                'id' => $id,
                'key' => $name,
                'value' => $name,
                'email' => $email,
                'avatar' => $avatar,
                'link' => url($link),
            ];
        })->toArray();
        $this->form->fill();
        $this->voteForm->fill();
        $this->eventForm->fill();
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

        $urlsJson = json_encode($urls);
        $this->js("window.__insertCommentImages({$urlsJson})");
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

        $filesJson = json_encode($files);
        $this->js("window.__insertCommentFiles({$filesJson})");
    }

    public function setCommentType(string $type): void
    {
        $enumType = CommentType::tryFrom($type);

        if (! $enumType) {
            return;
        }

        $this->commentType = $enumType->value;
    }

    public function getActiveCommentType(): CommentType
    {
        return CommentType::tryFrom($this->commentType) ?? CommentType::Text;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TributeTextarea::make('comment')
                    ->hiddenLabel()
                    ->required()
                    ->urlPattern('/users/{id}/profile')
                    ->mentionables($this->mentionables)
                    ->channelMentionables($this->getChannelMentionables())
                    ->projectMentionables($this->getProjectMentionables())
                    ->taskMentionables($this->getTaskMentionables())
                    ->placeholder(config('codenzia-comments.editor.placeholder', '')),
            ])
            ->statePath('data');
    }

    public function voteForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('question')
                    ->label(__('codenzia-comments::codenzia-comments.comment_types.poll_question'))
                    ->required()
                    ->placeholder(__('codenzia-comments::codenzia-comments.comment_types.poll_question_placeholder')),
                Repeater::make('options')
                    ->label(__('codenzia-comments::codenzia-comments.comment_types.poll_options'))
                    ->simple(
                        TextInput::make('option')
                            ->required()
                            ->placeholder(__('codenzia-comments::codenzia-comments.comment_types.poll_option_placeholder')),
                    )
                    ->minItems(2)
                    ->maxItems(10)
                    ->defaultItems(2)
                    ->addActionLabel(__('codenzia-comments::codenzia-comments.comment_types.poll_add_option'))
                    ->reorderable(false),
            ])
            ->statePath('voteData');
    }

    public function eventForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label(__('codenzia-comments::codenzia-comments.comment_types.event_title'))
                    ->required()
                    ->placeholder(__('codenzia-comments::codenzia-comments.comment_types.event_title_placeholder')),
                DateTimePicker::make('date')
                    ->label(__('codenzia-comments::codenzia-comments.comment_types.event_date'))
                    ->required()
                    ->native(false),
                Textarea::make('description')
                    ->label(__('codenzia-comments::codenzia-comments.comment_types.event_description'))
                    ->placeholder(__('codenzia-comments::codenzia-comments.comment_types.event_description_placeholder'))
                    ->rows(2),
            ])
            ->statePath('eventData');
    }

    protected function getChannelMentionables(): array
    {
        return $this->getAvailableChannels()->map(function ($channel) {
            return [
                'id' => $channel->id,
                'key' => $channel->name,
                'value' => $channel->name,
                'slug' => $channel->slug,
                'link' => \Codenzia\FilamentComments\Filament\Pages\DiscussionPage::getUrl(['record' => $channel->id]),
            ];
        })->toArray();
    }

    protected function getProjectMentionables(): array
    {
        $projectModel = config('codenzia-comments.project_mentionable.model');
        if (! $projectModel || ! class_exists($projectModel)) {
            return [];
        }

        $labelColumn = config('codenzia-comments.project_mentionable.column.label', 'title');
        $idColumn = config('codenzia-comments.project_mentionable.column.id', 'id');
        $urlPattern = config('codenzia-comments.project_mentionable.url', 'admin/projects/{id}');

        return $projectModel::all()->map(function ($project) use ($labelColumn, $idColumn, $urlPattern) {
            return [
                'id' => $project->{$idColumn},
                'key' => $project->{$labelColumn},
                'value' => $project->{$labelColumn},
                'link' => url(str_replace('{id}', $project->{$idColumn}, $urlPattern)),
            ];
        })->toArray();
    }

    protected function getTaskMentionables(): array
    {
        $taskModel = config('codenzia-comments.task_mentionable.model');
        if (! $taskModel || ! class_exists($taskModel)) {
            return [];
        }

        $labelColumn = config('codenzia-comments.task_mentionable.column.label', 'title');
        $idColumn = config('codenzia-comments.task_mentionable.column.id', 'id');
        $urlPattern = config('codenzia-comments.task_mentionable.url', 'admin/tasks/{id}');

        return $taskModel::all()->map(function ($task) use ($labelColumn, $idColumn, $urlPattern) {
            return [
                'id' => $task->{$idColumn},
                'key' => $task->{$labelColumn},
                'value' => $task->{$labelColumn},
                'link' => url(str_replace('{id}', $task->{$idColumn}, $urlPattern)),
            ];
        })->toArray();
    }

    public function create(): void
    {
        if (! $this->canUserPostInChannel()) {
            Notification::make()
                ->title('Unauthorized')
                ->body('Only members can post in this channel.')
                ->danger()
                ->send();

            return;
        }

        $activeType = $this->getActiveCommentType();

        $commentBody = match ($activeType) {
            CommentType::Text => $this->createTextComment(),
            CommentType::Vote => $this->createVoteComment(),
            CommentType::Event => $this->createEventComment(),
        };

        if ($commentBody === null) {
            return;
        }

        $commentType = $activeType === CommentType::Text ? CommentType::Text : $activeType;

        $comment = $this->record->comments()->create([
            'comment' => $commentBody,
            'type' => $commentType->value,
            'user_id' => auth()->id(),
            'channel_id' => $this->activeChannelId,
        ]);

        // Detect mentions in the comment and send notifications
        $mentionedNames = $this->extractMentions($commentBody);
        if (! empty($mentionedNames)) {
            $userModel = $this->getUserModelClass();
            $columnName = config('codenzia-comments.mentionable.column.label', 'name');
            foreach ($mentionedNames as $name) {
                $mentionedUser = $userModel::where($columnName, $name)->first();
                if ($mentionedUser) {
                    if ($mentionedUser && $mentionedUser->id !== auth()->id()) {
                        event(new UserMentioned($mentionedUser, $comment->comment, auth()->user()));
                    }
                }
            }
        }

        Notification::make()
            ->title(__('codenzia-comments::codenzia-comments.notifications.created'))
            ->success()
            ->send();

        $this->resetForms();
    }

    protected function createTextComment(): ?string
    {
        $data = $this->form->getState();

        return $data['comment'];
    }

    protected function createVoteComment(): ?string
    {
        $data = $this->voteForm->getState();

        $votePayload = [
            'question' => $data['question'],
            'options' => collect($data['options'])->values()->all(),
            'votes' => [],
        ];

        return json_encode($votePayload);
    }

    protected function createEventComment(): ?string
    {
        $data = $this->eventForm->getState();

        $eventPayload = [
            'title' => $data['title'],
            'date' => $data['date'],
            'description' => $data['description'] ?? '',
        ];

        return json_encode($eventPayload);
    }

    protected function resetForms(): void
    {
        $this->form->fill();
        $this->voteForm->fill();
        $this->eventForm->fill();
        $this->tempImages = [];
        $this->tempFiles = [];
        $this->commentType = CommentType::Text->value;
    }

    public function castVote(int $commentId, int $optionIndex): void
    {
        $comment = Comment::find($commentId);

        if (! $comment || $comment->type !== CommentType::Vote) {
            return;
        }

        $data = $comment->getDecodedComment();
        $votes = $data['votes'] ?? [];
        $userId = (string) auth()->id();

        if (isset($votes[$userId]) && $votes[$userId] === $optionIndex) {
            unset($votes[$userId]);
        } else {
            $votes[$userId] = $optionIndex;
        }

        $data['votes'] = $votes;

        $comment->update([
            'comment' => json_encode($data),
        ]);

        $this->dispatch('voteUpdated');
    }

    public function respondToEvent(int $commentId, string $status): void
    {
        $allowedStatuses = ['going', 'maybe', 'not_going'];

        if (! in_array($status, $allowedStatuses, true)) {
            return;
        }

        $comment = Comment::find($commentId);

        if (! $comment || $comment->type !== CommentType::Event) {
            return;
        }

        $data = $comment->getDecodedComment();
        $responses = $data['responses'] ?? [];
        $userId = (string) auth()->id();

        if (isset($responses[$userId]) && $responses[$userId] === $status) {
            unset($responses[$userId]);
        } else {
            $responses[$userId] = $status;
        }

        $data['responses'] = $responses;

        $comment->update([
            'comment' => json_encode($data),
        ]);

        $this->dispatch('eventResponseUpdated');
    }

    public function addToCalendar(int $commentId): void
    {
        $comment = Comment::find($commentId);

        if (! $comment || $comment->type !== CommentType::Event) {
            Notification::make()
                ->title(__('codenzia-comments::codenzia-comments.notifications.invalid_event'))
                ->danger()
                ->send();

            return;
        }

        $eventData = $comment->getDecodedComment();
        $title = $eventData['title'] ?? '';
        $date = $eventData['date'] ?? null;
        $description = $eventData['description'] ?? '';

        if (! $date) {
            Notification::make()
                ->title(__('codenzia-comments::codenzia-comments.notifications.event_no_date'))
                ->warning()
                ->send();

            return;
        }

        // Dispatch an event that the app can listen to for calendar integration
        event(new \Codenzia\FilamentComments\Events\EventAddedToCalendar($comment, $eventData));

        Notification::make()
            ->title(__('codenzia-comments::codenzia-comments.notifications.event_added_to_calendar'))
            ->success()
            ->send();
    }

    public function canUserPostInChannel(): bool
    {
        $channel = CommentChannel::find($this->activeChannelId);

        return $channel->members()->where('user_id', auth()->id())->exists();
    }

    public function joinChannel(): void
    {
        if (! $this->activeChannelId) {
            return;
        }

        $channel = CommentChannel::find($this->activeChannelId);

        if (! $channel) {
            return;
        }

        $channel->members()->syncWithoutDetaching([auth()->id()]);

        $this->dispatch('joinedChannel');

        Notification::make()
            ->title(__('Joined successfully'))
            ->body(__('You are now a member of ' . $channel->name))
            ->success()
            ->send();
    }

    public function delete(int $id): void
    {
        $comment = Comment::find($id);

        if (! $comment) {
            return;
        }

        $comment->delete();

        Notification::make()
            ->title(__('codenzia-comments::codenzia-comments.notifications.deleted'))
            ->success()
            ->send();
    }

    /**
     * Get full avatar URL or generate UI Avatars URL if null
     */
    protected function getAvatarUrl(?string $avatarPath, ?string $name): string
    {
        // If avatar path is null or empty, generate UI Avatars URL
        if (empty($avatarPath)) {
            $panelColor = config('filament.default_color', '000000');
            $panelColor = str_replace('#', '', $panelColor);

            return 'https://ui-avatars.com/api/?name=' . urlencode($name ?? 'User') . '&color=FFFFFF&background=' . $panelColor;
        }

        // If it's already a full URL, return it
        if (filter_var($avatarPath, FILTER_VALIDATE_URL)) {
            return $avatarPath;
        }

        // Convert storage path to full URL
        if (Storage::disk('public')->exists($avatarPath)) {
            return Storage::disk('public')->url($avatarPath);
        }

        // Fallback to UI Avatars if file doesn't exist
        $panelColor = config('filament.default_color', '000000');
        $panelColor = str_replace('#', '', $panelColor);

        return 'https://ui-avatars.com/api/?name=' . urlencode($name ?? 'User') . '&color=FFFFFF&background=' . $panelColor;
    }

    public function setActiveChannel(int $id): void
    {
        $this->activeChannelId = $id;
    }

    public function getAvailableChannels()
    {
        return CommentChannel::all()->filter(function ($channel) {
            if (empty($channel->permissions)) {
                return true;
            }

            foreach ($channel->permissions as $permission) {
                if (auth()->user()?->can($permission)) {
                    return true;
                }
            }

            return false;
        });
    }

    public function render(): View
    {
        $availableChannels = $this->getAvailableChannels();

        if ($this->activeChannelId && ! $availableChannels->pluck('id')->contains($this->activeChannelId)) {
            $this->activeChannelId = $availableChannels->first()?->id;
        }

        $query = $this->record->comments()
            ->whereNull('parent_id')
            ->with(['commentator', 'replies.commentator', 'reactions', 'replies.reactions']);

        if ($this->activeChannelId) {
            $query->where('channel_id', $this->activeChannelId);
        }

        return view('codenzia-comments::livewire.comments', [
            'comments' => $query->latest()->get(),
            'channels' => $availableChannels,
            'channelMentionables' => $this->getChannelMentionables(),
            'canPost' => $this->canUserPostInChannel(),
        ]);
    }
}
