<?php

namespace Codenzia\FilamentComments\Livewire;

use Codenzia\FilamentComments\Enums\CommentType;
use Codenzia\FilamentComments\Events\EventAddedToCalendar;
use Codenzia\FilamentComments\Events\UserMentioned;
use Codenzia\FilamentComments\Filament\Pages\DiscussionPage;
use Codenzia\FilamentComments\Forms\TributeTextarea;
use Codenzia\FilamentComments\Models\Comment;
use Codenzia\FilamentComments\Models\CommentChannel;
use Codenzia\FilamentComments\Services\LinkPreviewService;
use Codenzia\FilamentComments\Traits\ExtractsMentions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
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

    public ?array $meetingData = [];

    public ?array $todoData = [];

    public ?array $surveyData = [];

    public ?array $riskData = [];

    public string $commentType = 'text';

    /** @var array<TemporaryUploadedFile> */
    public $tempImages = [];

    /** @var array<TemporaryUploadedFile> */
    public $tempFiles = [];

    public Model $record;

    public array $mentionables = [];

    public ?int $activeChannelId = null;

    public bool $showResolved = false;

    protected $listeners = [
        'commentDeleted' => '$refresh',
        'reactionUpdated' => '$refresh',
        'commentPinned' => '$refresh',
        'commentResolved' => '$refresh',
        'watchToggled' => '$refresh',
    ];

    public function mount(Model $record, array $mentionables = [], ?int $activeChannelId = null): void
    {
        $this->record = $record;

        $availableChannels = $this->getAvailableChannels();

        $this->activeChannelId = $activeChannelId
            ?? $availableChannels->first()?->id;

        if (empty($mentionables)) {
            $userModel = config('filament-comments.mentionable.model');
            if ($userModel && class_exists($userModel)) {
                $mentionables = $userModel::all();
            }
        }

        $labelColumn = config('filament-comments.mentionable.column.label', 'name');
        $emailColumn = config('filament-comments.mentionable.column.email', 'email');
        $avatarColumn = config('filament-comments.mentionable.column.avatar', 'avatar');
        $idColumn = config('filament-comments.mentionable.column.id', 'id');

        $this->mentionables = collect($mentionables)->map(function ($user) use ($labelColumn, $emailColumn, $avatarColumn, $idColumn) {
            $name = is_array($user) ? Arr::get($user, $labelColumn) : ($user->{$labelColumn} ?? null);
            $email = is_array($user) ? Arr::get($user, $emailColumn) : ($user->{$emailColumn} ?? null);
            $avatarPath = is_array($user) ? Arr::get($user, $avatarColumn) : ($user->{$avatarColumn} ?? null);
            $id = is_array($user) ? Arr::get($user, $idColumn) : ($user->{$idColumn} ?? null);

            // Get full avatar URL or generate UI Avatars URL
            $avatar = $this->getAvatarUrl($avatarPath, $name);

            $urlPattern = config('filament-comments.mentionable.url', 'admin/users/{id}');
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
        $this->meetingForm->fill();
        $this->todoForm->fill();
        $this->surveyForm->fill();
        $this->riskForm->fill();
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
                    ->placeholder(config('filament-comments.editor.placeholder', '')),
            ])
            ->statePath('data');
    }

    public function voteForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('question')
                    ->label(__('filament-comments::messages.comment_types.poll_question'))
                    ->required()
                    ->placeholder(__('filament-comments::messages.comment_types.poll_question_placeholder')),
                Repeater::make('options')
                    ->label(__('filament-comments::messages.comment_types.poll_options'))
                    ->simple(
                        TextInput::make('option')
                            ->required()
                            ->placeholder(__('filament-comments::messages.comment_types.poll_option_placeholder')),
                    )
                    ->minItems(2)
                    ->maxItems(10)
                    ->defaultItems(2)
                    ->addActionLabel(__('filament-comments::messages.comment_types.poll_add_option'))
                    ->reorderable(false),
            ])
            ->statePath('voteData');
    }

    public function eventForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label(__('filament-comments::messages.comment_types.event_title'))
                    ->required()
                    ->placeholder(__('filament-comments::messages.comment_types.event_title_placeholder')),
                DateTimePicker::make('date')
                    ->label(__('filament-comments::messages.comment_types.event_date'))
                    ->required()
                    ->native(false),
                Textarea::make('description')
                    ->label(__('filament-comments::messages.comment_types.event_description'))
                    ->placeholder(__('filament-comments::messages.comment_types.event_description_placeholder'))
                    ->rows(2),
            ])
            ->statePath('eventData');
    }

    public function meetingForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label(__('filament-comments::messages.comment_types.meeting_title'))
                    ->required()
                    ->placeholder(__('filament-comments::messages.comment_types.meeting_title_placeholder')),
                DateTimePicker::make('start_at')
                    ->label(__('filament-comments::messages.comment_types.meeting_start'))
                    ->required()
                    ->native(false),
                DateTimePicker::make('end_at')
                    ->label(__('filament-comments::messages.comment_types.meeting_end'))
                    ->native(false),
                TextInput::make('google_meet_link')
                    ->label(__('filament-comments::messages.comment_types.meeting_link'))
                    ->placeholder(__('filament-comments::messages.comment_types.meeting_link_placeholder'))
                    ->url(),
                Textarea::make('description')
                    ->label(__('filament-comments::messages.comment_types.meeting_description'))
                    ->placeholder(__('filament-comments::messages.comment_types.meeting_description_placeholder'))
                    ->rows(2),
            ])
            ->statePath('meetingData');
    }

    public function todoForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Repeater::make('items')
                    ->label(__('filament-comments::messages.comment_types.todo_items'))
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->placeholder(__('filament-comments::messages.comment_types.todo_item_placeholder'))
                            ->columnSpan(2),
                        Select::make('priority')
                            ->label(__('filament-comments::messages.comment_types.todo_priority'))
                            ->options([
                                'low' => __('filament-comments::messages.comment_types.todo_priority_low'),
                                'medium' => __('filament-comments::messages.comment_types.todo_priority_medium'),
                                'high' => __('filament-comments::messages.comment_types.todo_priority_high'),
                            ])
                            ->default('medium'),
                    ])
                    ->columns(3)
                    ->minItems(1)
                    ->maxItems(20)
                    ->defaultItems(1)
                    ->addActionLabel(__('filament-comments::messages.comment_types.todo_add_item'))
                    ->reorderable(),
            ])
            ->statePath('todoData');
    }

    public function surveyForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label(__('filament-comments::messages.comment_types.survey_title'))
                    ->required()
                    ->placeholder(__('filament-comments::messages.comment_types.survey_title_placeholder')),
                Textarea::make('description')
                    ->label(__('filament-comments::messages.comment_types.survey_description'))
                    ->placeholder(__('filament-comments::messages.comment_types.survey_description_placeholder'))
                    ->rows(2),
                Repeater::make('questions')
                    ->label(__('filament-comments::messages.comment_types.survey_questions'))
                    ->schema([
                        TextInput::make('content')
                            ->label(__('filament-comments::messages.comment_types.survey_question'))
                            ->required()
                            ->placeholder(__('filament-comments::messages.comment_types.survey_question_placeholder')),
                        Select::make('type')
                            ->label(__('filament-comments::messages.comment_types.survey_question_type'))
                            ->options([
                                'text' => __('filament-comments::messages.comment_types.survey_type_text'),
                                'choice' => __('filament-comments::messages.comment_types.survey_type_choice'),
                                'rating' => __('filament-comments::messages.comment_types.survey_type_rating'),
                            ])
                            ->default('text')
                            ->required(),
                        Repeater::make('options')
                            ->label(__('filament-comments::messages.comment_types.survey_options'))
                            ->simple(
                                TextInput::make('option')
                                    ->required()
                                    ->placeholder(__('filament-comments::messages.comment_types.survey_option_placeholder')),
                            )
                            ->minItems(2)
                            ->maxItems(6)
                            ->defaultItems(2)
                            ->addActionLabel(__('filament-comments::messages.comment_types.survey_add_option'))
                            ->reorderable(false)
                            ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get): bool => $get('type') === 'choice'),
                    ])
                    ->minItems(1)
                    ->maxItems(10)
                    ->defaultItems(1)
                    ->addActionLabel(__('filament-comments::messages.comment_types.survey_add_question'))
                    ->reorderable(),
            ])
            ->statePath('surveyData');
    }

    public function riskForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label(__('filament-comments::messages.comment_types.risk_title'))
                    ->required()
                    ->placeholder(__('filament-comments::messages.comment_types.risk_title_placeholder')),
                Select::make('category')
                    ->label(__('filament-comments::messages.comment_types.risk_category'))
                    ->options([
                        'technical' => __('filament-comments::messages.comment_types.risk_cat_technical'),
                        'schedule' => __('filament-comments::messages.comment_types.risk_cat_schedule'),
                        'budget' => __('filament-comments::messages.comment_types.risk_cat_budget'),
                        'resource' => __('filament-comments::messages.comment_types.risk_cat_resource'),
                        'scope' => __('filament-comments::messages.comment_types.risk_cat_scope'),
                        'security' => __('filament-comments::messages.comment_types.risk_cat_security'),
                        'other' => __('filament-comments::messages.comment_types.risk_cat_other'),
                    ])
                    ->required(),
                Select::make('likelihood')
                    ->label(__('filament-comments::messages.comment_types.risk_likelihood'))
                    ->options([
                        'rare' => __('filament-comments::messages.comment_types.risk_likelihood_rare'),
                        'unlikely' => __('filament-comments::messages.comment_types.risk_likelihood_unlikely'),
                        'possible' => __('filament-comments::messages.comment_types.risk_likelihood_possible'),
                        'likely' => __('filament-comments::messages.comment_types.risk_likelihood_likely'),
                        'almost_certain' => __('filament-comments::messages.comment_types.risk_likelihood_almost_certain'),
                    ])
                    ->required(),
                Select::make('impact')
                    ->label(__('filament-comments::messages.comment_types.risk_impact'))
                    ->options([
                        'negligible' => __('filament-comments::messages.comment_types.risk_impact_negligible'),
                        'minor' => __('filament-comments::messages.comment_types.risk_impact_minor'),
                        'moderate' => __('filament-comments::messages.comment_types.risk_impact_moderate'),
                        'major' => __('filament-comments::messages.comment_types.risk_impact_major'),
                        'critical' => __('filament-comments::messages.comment_types.risk_impact_critical'),
                    ])
                    ->required(),
                Textarea::make('mitigation_plan')
                    ->label(__('filament-comments::messages.comment_types.risk_mitigation'))
                    ->placeholder(__('filament-comments::messages.comment_types.risk_mitigation_placeholder'))
                    ->rows(2),
            ])
            ->statePath('riskData');
    }

    protected function getChannelMentionables(): array
    {
        return $this->getAvailableChannels()->map(function ($channel) {
            return [
                'id' => $channel->id,
                'key' => $channel->name,
                'value' => $channel->name,
                'slug' => $channel->slug,
                'link' => DiscussionPage::getUrl(['record' => $channel->id]),
            ];
        })->toArray();
    }

    protected function getProjectMentionables(): array
    {
        $projectModel = config('filament-comments.project_mentionable.model');
        if (! $projectModel || ! class_exists($projectModel)) {
            return [];
        }

        $labelColumn = config('filament-comments.project_mentionable.column.label', 'title');
        $idColumn = config('filament-comments.project_mentionable.column.id', 'id');
        $urlPattern = config('filament-comments.project_mentionable.url', 'admin/projects/{id}');

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
        $taskModel = config('filament-comments.task_mentionable.model');
        if (! $taskModel || ! class_exists($taskModel)) {
            return [];
        }

        $labelColumn = config('filament-comments.task_mentionable.column.label', 'title');
        $idColumn = config('filament-comments.task_mentionable.column.id', 'id');
        $urlPattern = config('filament-comments.task_mentionable.url', 'admin/tasks/{id}');

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
                ->title(__('filament-comments::messages.notifications.unauthorized'))
                ->body(__('filament-comments::messages.notifications.unauthorized_members_only'))
                ->danger()
                ->send();

            return;
        }

        $activeType = $this->getActiveCommentType();

        $commentBody = match ($activeType) {
            CommentType::Text => $this->createTextComment(),
            CommentType::Vote => $this->createVoteComment(),
            CommentType::Event => $this->createEventComment(),
            CommentType::Meeting => $this->createMeetingComment(),
            CommentType::Todo => $this->createTodoComment(),
            CommentType::Survey => $this->createSurveyComment(),
            CommentType::Risk => $this->createRiskComment(),
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
            'is_approved' => config('filament-comments.auto_approve', true),
        ]);

        if ($commentType === CommentType::Event) {
            $this->storeEventModel($comment);
        }

        // Fetch and store link previews
        if ($commentType === CommentType::Text && config('filament-comments.link_previews.enabled', true)) {
            try {
                $linkPreviewService = app(LinkPreviewService::class);
                $previews = $linkPreviewService->fetchPreviews($commentBody);
                if (! empty($previews)) {
                    $comment->update(['link_previews' => $previews]);
                }
            } catch (\Throwable) {
                // Silently ignore link preview errors
            }
        }

        // Detect mentions in the comment and send notifications
        $mentionedNames = $this->extractMentions($commentBody);
        if (! empty($mentionedNames)) {
            $userModel = $this->getUserModelClass();
            $columnName = config('filament-comments.mentionable.column.label', 'name');
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
            ->title(__('filament-comments::messages.notifications.created'))
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
            'options' => collect($data['options'])->map(fn ($opt) => is_array($opt) ? ($opt['option'] ?? reset($opt)) : (string) $opt)->values()->all(),
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

    protected function createMeetingComment(): ?string
    {
        $data = $this->meetingForm->getState();

        $meetingPayload = [
            'title' => $data['title'],
            'start_at' => $data['start_at'],
            'end_at' => $data['end_at'] ?? null,
            'google_meet_link' => $data['google_meet_link'] ?? null,
            'description' => $data['description'] ?? '',
            'attendees' => [],
        ];

        return json_encode($meetingPayload);
    }

    protected function createTodoComment(): ?string
    {
        $data = $this->todoForm->getState();

        $todoPayload = [
            'items' => collect($data['items'] ?? [])->map(fn (array $item) => [
                'title' => $item['title'],
                'priority' => $item['priority'] ?? 'medium',
                'done' => false,
            ])->values()->all(),
        ];

        return json_encode($todoPayload);
    }

    protected function createSurveyComment(): ?string
    {
        $data = $this->surveyForm->getState();

        $surveyPayload = [
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'questions' => collect($data['questions'] ?? [])->map(fn (array $q) => [
                'content' => $q['content'],
                'type' => $q['type'] ?? 'text',
                'options' => $q['type'] === 'choice'
                    ? collect($q['options'] ?? [])->map(fn ($opt) => is_array($opt) ? ($opt['option'] ?? reset($opt)) : (string) $opt)->values()->all()
                    : [],
                'responses' => [],
            ])->values()->all(),
        ];

        return json_encode($surveyPayload);
    }

    protected function createRiskComment(): ?string
    {
        $data = $this->riskForm->getState();

        $riskPayload = [
            'title' => $data['title'],
            'category' => $data['category'],
            'likelihood' => $data['likelihood'],
            'impact' => $data['impact'],
            'mitigation_plan' => $data['mitigation_plan'] ?? '',
            'acknowledged_by' => [],
        ];

        return json_encode($riskPayload);
    }

    protected function storeEventModel(Comment $comment): void
    {
        $eventModel = config('filament-comments.event_model');

        if (! $eventModel || ! class_exists($eventModel)) {
            return;
        }

        $columns = config('filament-comments.event_model_columns', []);
        $payload = $comment->getDecodedComment();

        if (! isset($payload['title'], $payload['date'])) {
            return;
        }

        // If we have a mapped comment_id column, avoid creating duplicate event
        // records for the same comment.
        if (isset($columns['comment_id'])) {
            $commentIdColumn = $columns['comment_id'];

            try {
                if ($eventModel::where($commentIdColumn, $comment->id)->exists()) {
                    return;
                }
            } catch (\Throwable $e) {
                // If the check fails (e.g. column missing), fall through and try create().
            }
        }

        $attributes = [];

        if (isset($columns['title'])) {
            $attributes[$columns['title']] = $payload['title'];
        }

        if (isset($columns['date'])) {
            $attributes[$columns['date']] = $payload['date'];
        }

        if (isset($columns['description'])) {
            $attributes[$columns['description']] = $payload['description'] ?? null;
        }

        if (isset($columns['comment_id'])) {
            $attributes[$columns['comment_id']] = $comment->id;
        }

        if (isset($columns['user_id'])) {
            $attributes[$columns['user_id']] = auth()->id();
        }

        if (empty($attributes)) {
            return;
        }

        try {
            /** @var class-string<Model> $eventModel */
            $eventModel::create($attributes);
        } catch (\Throwable $e) {
            // Silently ignore persistence errors to avoid breaking comments
        }
    }

    protected function resetForms(): void
    {
        $this->form->fill();
        $this->voteForm->fill();
        $this->eventForm->fill();
        $this->meetingForm->fill();
        $this->todoForm->fill();
        $this->surveyForm->fill();
        $this->riskForm->fill();
        $this->tempImages = [];
        $this->tempFiles = [];
        $this->commentType = CommentType::Text->value;
    }

    public function castVote(int $commentId, int $optionIndex): void
    {
        DB::transaction(function () use ($commentId, $optionIndex) {
            $comment = Comment::lockForUpdate()->find($commentId);

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
        });

        $this->dispatch('voteUpdated');
    }

    public function respondToEvent(int $commentId, string $status): void
    {
        $allowedStatuses = ['going', 'maybe', 'not_going'];

        if (! in_array($status, $allowedStatuses, true)) {
            return;
        }

        DB::transaction(function () use ($commentId, $status) {
            $comment = Comment::lockForUpdate()->find($commentId);

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
        });

        $this->dispatch('eventResponseUpdated');
    }

    public function respondToMeeting(int $commentId, string $status): void
    {
        $allowedStatuses = ['attending', 'maybe', 'declined'];

        if (! in_array($status, $allowedStatuses, true)) {
            return;
        }

        DB::transaction(function () use ($commentId, $status) {
            $comment = Comment::lockForUpdate()->find($commentId);

            if (! $comment || $comment->type !== CommentType::Meeting) {
                return;
            }

            $data = $comment->getDecodedComment();
            $attendees = $data['attendees'] ?? [];
            $userId = (string) auth()->id();

            if (isset($attendees[$userId]) && $attendees[$userId] === $status) {
                unset($attendees[$userId]);
            } else {
                $attendees[$userId] = $status;
            }

            $data['attendees'] = $attendees;

            $comment->update([
                'comment' => json_encode($data),
            ]);
        });

        $this->dispatch('meetingResponseUpdated');
    }

    public function toggleTodoItem(int $commentId, int $itemIndex): void
    {
        DB::transaction(function () use ($commentId, $itemIndex) {
            $comment = Comment::lockForUpdate()->find($commentId);

            if (! $comment || $comment->type !== CommentType::Todo) {
                return;
            }

            $data = $comment->getDecodedComment();
            $items = $data['items'] ?? [];

            if (! isset($items[$itemIndex])) {
                return;
            }

            $items[$itemIndex]['done'] = ! ($items[$itemIndex]['done'] ?? false);
            $data['items'] = $items;

            $comment->update([
                'comment' => json_encode($data),
            ]);
        });

        $this->dispatch('todoUpdated');
    }

    public function respondToSurvey(int $commentId, int $questionIndex, mixed $answer): void
    {
        DB::transaction(function () use ($commentId, $questionIndex, $answer) {
            $comment = Comment::lockForUpdate()->find($commentId);

            if (! $comment || $comment->type !== CommentType::Survey) {
                return;
            }

            $data = $comment->getDecodedComment();
            $questions = $data['questions'] ?? [];

            if (! isset($questions[$questionIndex])) {
                return;
            }

            $userId = (string) auth()->id();
            $responses = $questions[$questionIndex]['responses'] ?? [];
            $responses[$userId] = $answer;
            $questions[$questionIndex]['responses'] = $responses;
            $data['questions'] = $questions;

            $comment->update([
                'comment' => json_encode($data),
            ]);
        });

        $this->dispatch('surveyResponseUpdated');
    }

    public function acknowledgeRisk(int $commentId): void
    {
        DB::transaction(function () use ($commentId) {
            $comment = Comment::lockForUpdate()->find($commentId);

            if (! $comment || $comment->type !== CommentType::Risk) {
                return;
            }

            $data = $comment->getDecodedComment();
            $acknowledgedBy = $data['acknowledged_by'] ?? [];
            $userId = (string) auth()->id();

            if (in_array($userId, $acknowledgedBy, true)) {
                $acknowledgedBy = array_values(array_filter($acknowledgedBy, fn ($id) => $id !== $userId));
            } else {
                $acknowledgedBy[] = $userId;
            }

            $data['acknowledged_by'] = $acknowledgedBy;

            $comment->update([
                'comment' => json_encode($data),
            ]);
        });

        $this->dispatch('riskAcknowledged');
    }

    public function addToCalendar(int $commentId): void
    {
        $comment = Comment::find($commentId);

        if (! $comment || $comment->type !== CommentType::Event) {
            Notification::make()
                ->title(__('filament-comments::messages.notifications.invalid_event'))
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
                ->title(__('filament-comments::messages.notifications.event_no_date'))
                ->warning()
                ->send();

            return;
        }

        // Ensure the event is persisted to the configured Event model, if any.
        $this->storeEventModel($comment);

        // Dispatch an event that the app can listen to for calendar integration
        event(new EventAddedToCalendar($comment, $eventData));

        Notification::make()
            ->title(__('filament-comments::messages.notifications.event_added_to_calendar'))
            ->success()
            ->send();
    }

    public function canUserPostInChannel(): bool
    {
        // When the record is not a CommentChannel (e.g. Task, Project, Invoice),
        // anyone who can view the record can post comments — no channel membership required.
        if (! $this->record instanceof CommentChannel) {
            return true;
        }

        if (! $this->activeChannelId) {
            return false;
        }

        $channel = CommentChannel::find($this->activeChannelId);

        if (! $channel) {
            return false;
        }

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
            ->title(__('filament-comments::messages.notifications.joined_channel'))
            ->body(__('filament-comments::messages.notifications.joined_channel_body', ['name' => $channel->name]))
            ->success()
            ->send();
    }

    public function delete(int $id): void
    {
        $comment = Comment::find($id);

        if (! $comment) {
            return;
        }

        if (auth()->id() !== $comment->user_id && ! auth()->user()?->can('delete', $comment)) {
            Notification::make()
                ->title(__('filament-comments::messages.notifications.unauthorized'))
                ->danger()
                ->send();

            return;
        }

        $comment->delete();

        Notification::make()
            ->title(__('filament-comments::messages.notifications.deleted'))
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

    public function getAvailableChannels(): Collection
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

    // ── Pin ────────────────────────────────────────────────────────

    public function pinComment(int $commentId): void
    {
        $comment = Comment::find($commentId);

        if (! $comment) {
            return;
        }

        $comment->pin();

        Notification::make()
            ->title(__('filament-comments::messages.notifications.comment_pinned'))
            ->success()
            ->send();

        $this->dispatch('commentPinned');
    }

    public function unpinComment(int $commentId): void
    {
        $comment = Comment::find($commentId);

        if (! $comment) {
            return;
        }

        $comment->unpin();

        Notification::make()
            ->title(__('filament-comments::messages.notifications.comment_unpinned'))
            ->success()
            ->send();

        $this->dispatch('commentPinned');
    }

    // ── Watch / Unwatch ──────────────────────────────────────────

    public function toggleWatch(): void
    {
        $isWatching = $this->record->toggleWatch();

        Notification::make()
            ->title($isWatching
                ? __('filament-comments::messages.notifications.watching')
                : __('filament-comments::messages.notifications.unwatched'))
            ->success()
            ->send();

        $this->dispatch('watchToggled');
    }

    // ── Resolved Toggle ──────────────────────────────────────────

    public function toggleShowResolved(): void
    {
        $this->showResolved = ! $this->showResolved;
    }

    public function render(): View
    {
        $availableChannels = $this->getAvailableChannels();

        if ($this->activeChannelId && ! $availableChannels->pluck('id')->contains($this->activeChannelId)) {
            $this->activeChannelId = $availableChannels->first()?->id;
        }

        $baseQuery = $this->record->comments()
            ->approved()
            ->whereNull('parent_id')
            ->with(['commentator', 'replies.commentator', 'reactions', 'replies.reactions', 'resolvedBy', 'bookmarks']);

        if ($this->activeChannelId) {
            $baseQuery->where('channel_id', $this->activeChannelId);
        }

        // Pinned comment (always shown at top)
        $pinnedComment = (clone $baseQuery)->pinned()->first();

        // Main comments query — exclude pinned, optionally filter resolved
        $query = (clone $baseQuery)->where('is_pinned', false);
        if (! $this->showResolved) {
            $query->unresolved();
        }

        $isWatching = method_exists($this->record, 'isWatchedBy') ? $this->record->isWatchedBy() : false;

        return view('filament-comments::livewire.comments', [
            'comments' => $query->oldest()->get(),
            'pinnedComment' => $pinnedComment,
            'channels' => $availableChannels,
            'channelMentionables' => $this->getChannelMentionables(),
            'canPost' => $this->canUserPostInChannel(),
            'isWatching' => $isWatching,
            'showResolved' => $this->showResolved,
        ]);
    }
}
