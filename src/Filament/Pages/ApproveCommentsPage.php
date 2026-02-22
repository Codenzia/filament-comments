<?php

namespace Codenzia\FilamentComments\Filament\Pages;

use Codenzia\FilamentComments\Models\Comment;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ApproveCommentsPage extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static \BackedEnum | string | null $navigationIcon = 'heroicon-o-check-circle';

    protected string $view = 'codenzia-comments::filament.pages.approve-comments-page';

    protected static ?string $slug = 'approve-comments';

    protected static ?string $navigationLabel = 'Approve Comments';

    protected static bool $shouldRegisterNavigation = false;

    public function getTitle(): string
    {
        return 'Approve Comments';
    }

    public static function getNavigationGroup(): ?string
    {
        return config('codenzia-comments.navigation_group');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Comment::query()->where('is_approved', false))
            ->columns([
                TextColumn::make('commentator.name')
                    ->label('User')
                    ->searchable(),
                TextColumn::make('comment')
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('channel.name')
                    ->label('Channel')
                    ->placeholder('General')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(fn (Comment $record) => $record->approve()),
                DeleteAction::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Comment $record) => $record->delete()),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(fn (Collection $records) => $records->each->approve()),
                ]),
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Comment::where('is_approved', false)->count();
    }
}
