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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class CommentsComponent extends Component implements HasActions, HasForms
{
    use ExtractsMentions;
    use InteractsWithActions;
    use InteractsWithForms;

    public ?array $data = [];

    public Model $record;

    public array $mentionables = [];

    protected $listeners = ['commentDeleted' => '$refresh', 'reactionUpdated' => '$refresh'];

    public function mount(Model $record, array $mentionables = []): void
    {
        $this->record = $record;

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
                'avatar' => $avatar,
                'link' => url($link),
            ];
        })->toArray();
        $this->form->fill();
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
                    ->placeholder(config('codenzia-comments.editor.placeholder', '')),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $comment = $this->record->comments()->create([
            'comment' => $data['comment'],
            'user_id' => auth()->id(),
        ]);

        // Detect mentions in the comment and send notifications
        $mentionedNames = $this->extractMentions($data['comment']);
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

        $this->form->fill();
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

    public function render(): View
    {
        return view('codenzia-comments::livewire.comments', [
            'comments' => $this->record->comments()
                ->whereNull('parent_id')
                ->with(['commentator', 'replies.commentator', 'reactions', 'replies.reactions'])
                ->latest()
                ->get(),
        ]);
    }
}
