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
use App\Models\User;

class CommentsComponent extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public Model $record;

    protected $listeners = ['commentDeleted' => '$refresh', 'reactionUpdated' => '$refresh'];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TributeTextarea::make('comment')
                    ->hiddenLabel()
                    ->required()
                    //->triggerWith(['@'])
                    ->mentionables(      
                        User::select('name', 'id')
                            ->get()
                            ->map(fn ($user) => [
                                'key' => $user->name, 
                                'value' => $user->name 
                            ])
                            ->toArray()
                            ),               
                    // ->placeholder(config('codenzia-comments.editor.placeholder', ''))

            ])
            ->statePath('data');
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $this->record->comments()->create([
            'comment' => $data['comment'],
            'user_id' => auth()->id(),
        ]);

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
