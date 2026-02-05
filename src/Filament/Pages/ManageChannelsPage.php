<?php

namespace Codenzia\FilamentComments\Filament\Pages;

use Codenzia\FilamentComments\Models\CommentChannel;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions\EditAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ManageChannelsPage extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected string $view = 'codenzia-comments::filament.pages.manage-channels-page';

    protected static ?string $navigationLabel = 'Manage Channels';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): ?string
    {
        return config('codenzia-comments.navigation_group');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(CommentChannel::query())
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('slug'),
                IconColumn::make('is_default')->boolean()->label('Default'),
            ])
            ->actions([
                EditAction::make()
                    ->form($this->getChannelFormSchema())
                    ->using(fn (CommentChannel $record, array $data) => $record->update($data)),
                DeleteAction::make()
                    ->hidden(fn (CommentChannel $record) => $record->is_default),
            ])
            ->headerActions([
                CreateAction::make()
                    ->form($this->getChannelFormSchema())
                    ->using(fn (array $data) => CommentChannel::create($data)),
            ]);
    }

    protected function getChannelFormSchema(): array
    {
        return [
            Section::make()->schema([
                TextInput::make('name')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state))),
                TextInput::make('slug')
                    ->required()
                    ->unique(CommentChannel::class, 'slug', ignoreRecord: true),
                Textarea::make('description')->columnSpanFull(),
                Toggle::make('is_default')->label('Default Channel'),
                TagsInput::make('permissions')
                    ->placeholder('Add roles/permissions'),
            ])->columns(2),
        ];
    }
}
