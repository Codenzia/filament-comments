<?php

namespace Codenzia\FilamentComments\Filament\Resources;

use Codenzia\FilamentComments\Filament\Resources\CommentChannelResource\Pages;
use Codenzia\FilamentComments\Models\CommentChannel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Schemas\Schema;
class CommentChannelResource extends Resource
{
    protected static ?string $model = CommentChannel::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-hashtag';

    protected static \BackedEnum|string|null $navigationLabel = 'Channels';

    protected static ?string $modelLabel = 'Channel';

    protected static ?string $pluralModelLabel = 'Channels';

    public static function getNavigationGroup(): ?string
    {
        return config('codenzia-comments.navigation_group');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),

                        Forms\Components\TextInput::make('slug')
                            ->disabled(fn (string $operation) => $operation === 'edit')
                            ->required()
                            ->unique(CommentChannel::class, 'slug', ignoreRecord: true),

                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_default')
                            ->label('Default Channel')
                            ->helperText('This channel will be selected by default for new comments.')
                            ->default(false),

                        Forms\Components\TagsInput::make('permissions')
                            ->label('Allowed Roles/Permissions')
                            ->placeholder('Add roles or permissions that can see this channel')
                            ->helperText('Leave empty to allow everyone to see this channel.'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_default')
                    ->boolean()
                    ->label('Default'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make()->hidden(fn ($record) => $record->is_default),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make()->action(fn (Tables\Actions\DeleteBulkAction $action) => $action->getRecords()->where('is_default', false)->each->delete()),
                // ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommentChannels::route('/'),
            'create' => Pages\CreateCommentChannel::route('/create'),
            'edit' => Pages\EditCommentChannel::route('/{record}/edit'),
        ];
    }
}
