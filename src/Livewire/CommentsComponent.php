<?php

namespace Codenzia\FilamentComments\Livewire;

use Codenzia\FilamentComments\Models\Comment;
use Codenzia\FilamentComments\Forms\TributeTextarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use \Codenzia\FilamentComments\Events\UserMentioned;
use Illuminate\Support\Arr;

class CommentsComponent extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public Model $record;

    public array $mentionables = [];

    protected $listeners = ['commentDeleted' => '$refresh', 'reactionUpdated' => '$refresh'];

    public function mount(Model $record, array $mentionables = []): void
    {
        $this->record = $record;
        $this->mentionables = collect($mentionables)->map(function ($user) {
            $name = is_array($user) ? Arr::get($user, 'name') : $user->name;
            return ['key' => $name, 'value' => $name];
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
                    ->placeholder(config('codenzia-comments.editor.placeholder', ''))
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

        // Detect mentions in the comment (e.g., @username)
        preg_match_all('/@([\w.]+)/', $data['comment'], $matches);
        $mentionedNames = $matches[1] ?? [];
        if (!empty($mentionedNames)) {
            $userModel = config('filament-comments.user_model') ?? \App\Models\User::class;
            foreach ($mentionedNames as $name) {
                $mentionedUser = $userModel::where('name', $name)->first();
                if ($mentionedUser) {
                    event(new UserMentioned($mentionedUser, $comment->comment, auth()->user()));
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
