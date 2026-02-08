<?php

namespace Codenzia\FilamentComments\Filament\Pages;

use Codenzia\FilamentComments\Models\CommentChannel;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
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

    protected static ?string $slug = 'manage-channels';

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
                IconColumn::make('icon')
                    ->icon(fn (?string $state): string => $state ?: 'heroicon-o-hashtag')
                    ->label('Icon'),
                TextColumn::make('name'),
                TextColumn::make('slug'),
            ])
            ->actions([
                EditAction::make()
                    ->slideOver()
                    ->form(static::getChannelFormSchema())
                    ->fillForm(fn (CommentChannel $record): array => [
                        ...$record->toArray(),
                        'members' => $record->members()->pluck('users.id')->toArray(),
                    ])
                    ->using(function (CommentChannel $record, array $data): void {
                        $members = $data['members'] ?? [];
                        unset($data['members']);
                        $record->update($data);
                        $record->members()->sync($members);
                    }),
                DeleteAction::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->slideOver()
                    ->label('Create Channel')
                    ->form(static::getChannelFormSchema())
                    ->using(function (array $data): CommentChannel {
                        $members = collect($data['members'] ?? [])
                            ->push(auth()->id())
                            ->filter()
                            ->unique()
                            ->values()
                            ->toArray();
                        unset($data['members']);
                        $channel = CommentChannel::create($data);
                        $channel->members()->sync($members);

                        return $channel;
                    }),
            ]);
    }

    public static function getChannelFormSchema(): array
    {
        $projectModel = config('codenzia-comments.project_model');
        $userModel = config('codenzia-comments.user_model') ?? config('auth.providers.users.model', \App\Models\User::class);

        return [
            Section::make()->schema([
                TextInput::make('name')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state))),
                TextInput::make('slug')
                    ->required()
                    ->unique(CommentChannel::class, 'slug', ignoreRecord: true),
                Select::make('icon')
                    ->label('Icon')
                    ->searchable()
                    ->allowHtml()
                    ->options(function (): array {
                        return collect(Heroicon::cases())
                            ->filter(fn (Heroicon $case): bool => str_starts_with($case->value, 'o-'))
                            ->mapWithKeys(function (Heroicon $case): array {
                                $value = 'heroicon-'.$case->value;
                                $label = str($case->name)->after('Outlined')->headline()->toString();
                                $svg = \Illuminate\Support\Facades\Blade::render(
                                    '<x-filament::icon :icon="$icon" class="h-5 w-5" />',
                                    ['icon' => $value],
                                );

                                return [$value => '<div class="flex items-center gap-2">'.$svg.'<span>'.e($label).'</span></div>'];
                            })
                            ->toArray();
                    }),
                Select::make('visibility')
                    ->options([
                    'public' => 'Public',
                    'private' => 'Private',
                    ])
                    ->default('public')
                    ->required(),
                Select::make('project_id')
                    ->label('Project')
                    ->options(fn () => $projectModel && class_exists($projectModel)
                    ? $projectModel::query()->pluck('title', 'id')
                    : [])
                    ->searchable()
                    ->preload()
                    ->visible(fn () => $projectModel && class_exists($projectModel)),
                Textarea::make('description')->columnSpanFull(),
                Select::make('members')
                    ->options(fn () => $userModel && class_exists($userModel)
                    ? $userModel::query()->pluck('name', 'id')
                    : [])
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->visible(fn () => $userModel && class_exists($userModel)),
            ])->columns(2),
        ];
    }
}
