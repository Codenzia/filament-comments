<?php

namespace Codenzia\FilamentComments\Filament\Pages;

use Codenzia\FilamentComments\Models\CommentChannel;
use Filament\Actions\ActionGroup;
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
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ManageChannelsPage extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static \BackedEnum | string | null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected string $view = 'filament-comments::filament.pages.manage-channels-page';

    protected static ?string $slug = 'manage-channels';

    protected static bool $shouldRegisterNavigation = false;

    public bool $shouldOpenCreateModal = false;

    public function mount(): void
    {
        abort_unless(static::can('view_channel'), 403);

        if (request()->boolean('create')) {
            $this->shouldOpenCreateModal = true;
        }
    }

    public static function getNavigationLabel(): string
    {
        return config('filament-comments.navigation_groups.channels', 'Channels');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('filament-comments.navigation_groups.channels', 'Channels');
    }

    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return config('filament-comments.navigation_groups.channels', 'Channels');
    }

    public function getHeading(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return config('filament-comments.navigation_groups.channels', 'Channels');
    }

    public function getSubheading(): string | \Illuminate\Contracts\Support\Htmlable | null
    {
        return 'Create, organize, and manage discussion channels';
    }

    /**
     * Check if the current user has the given comment-channel permission.
     *
     * Reads the permission name from config('filament-comments.permissions.{$ability}').
     * - If the config value is null, any authenticated user is allowed.
     * - Otherwise it delegates to Spatie Permission's `$user->can($permission)`,
     *   which works natively with Filament Shield.
     */
    public static function can(string $ability): bool
    {
        $permission = config("filament-comments.permissions.{$ability}");

        if ($permission === null) {
            return true;
        }

        return auth()->user()?->can($permission) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(CommentChannel::query()->channels())
            ->description('Public channels are visible to everyone. Private channels only appear in the sidebar for their members.')
            ->columns([
                IconColumn::make('icon')->icon(fn (?string $state): string => $state ?: 'heroicon-o-hashtag')->label('Icon'),
                TextColumn::make('name'),
                TextColumn::make('comments_count')
                    ->counts('comments')
                    ->label('Messages')
                    ->sortable(),
                TextColumn::make('channel_members_count')
                    ->counts('channelMembers')
                    ->label('Members')
                    ->sortable(),
                TextColumn::make('visibility')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'public' => 'success',
                        'private' => 'warning',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'public' => 'heroicon-o-globe-alt',
                        'private' => 'heroicon-o-lock-closed',
                        default => 'heroicon-o-question-mark-circle',
                    }),
                ToggleColumn::make('show_sidebar')
                    ->label('Show in Sidebar')
                    ->default(true)
                    ->afterStateUpdated(function () {
                        $this->dispatch('refresh-sidebar');
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->slideOver()
                        ->form(static::getChannelFormSchema())
                        ->fillForm(fn (CommentChannel $record): array => [
                            ...$record->toArray(),
                            'members' => $record->members()->pluck('users.id')->toArray(),
                        ])
                        ->visible(fn (CommentChannel $record): bool => $record->created_by === auth()->id() || static::can('update_channel'))
                        ->using(function (CommentChannel $record, array $data): void {
                            $members = $data['members'] ?? [];
                            unset($data['members']);
                            $record->update($data);
                            $record->members()->sync($members);
                            $this->dispatch('refresh-sidebar');
                        }),
                    DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Channel')
                        ->modalDescription('Are you sure you want to delete this channel? This action cannot be undone.')
                        ->modalSubmitActionLabel('Delete')
                        ->visible(fn (CommentChannel $record): bool => $record->created_by === auth()->id() || static::can('delete_channel'))
                        ->action(function (CommentChannel $record): void {
                            $record->delete();
                            $this->dispatch('refresh-sidebar');
                        }),
                ]),
            ])
            ->emptyStateHeading('No channels')
            ->emptyStateDescription('Create a channel to get started.')
            ->emptyStateIcon('heroicon-o-hashtag')
            ->recordUrl(fn (CommentChannel $record): string => DiscussionPage::getUrl(['record' => $record->id]))
            ->headerActions([
                CreateAction::make()
                    ->slideOver()
                    ->label('New Channel')
                    ->icon('heroicon-o-plus')
                    ->modalHeading(__('Create Channel'))
                    ->createAnother(false)
                    ->form(static::getChannelFormSchema())
                    ->visible(fn (): bool => static::can('create_channel'))
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
                        // refresh sidebar
                        $this->dispatch('refresh-sidebar');

                        return $channel;
                    }),
            ]);
    }

    public static function getChannelFormSchema(): array
    {
        $projectModel = config('filament-comments.project_model');
        $userModel = config('filament-comments.user_model') ?? config('auth.providers.users.model', \App\Models\User::class);

        return [
            Section::make()->schema([
                TextInput::make('name')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state))),
                TextInput::make('slug')
                    ->required()
                    ->hidden()
                    ->dehydrated()
                    ->unique(CommentChannel::class, 'slug', ignoreRecord: true),
                Select::make('icon')
                    ->label('Icon')
                    ->searchable()
                    ->allowHtml()
                    ->default('heroicon-o-hashtag')
                    ->options(function (): array {
                        return collect(Heroicon::cases())
                            ->filter(fn (Heroicon $case): bool => str_starts_with($case->value, 'o-'))
                            ->mapWithKeys(function (Heroicon $case): array {
                                $value = 'heroicon-' . $case->value;
                                $label = str($case->name)->after('Outlined')->headline()->toString();
                                $svg = \Illuminate\Support\Facades\Blade::render(
                                    '<x-filament::icon :icon="$icon" class="h-5 w-5" />',
                                    ['icon' => $value],
                                );

                                return [$value => '<div class="flex items-center gap-2">' . $svg . '<span>' . e($label) . '</span></div>'];
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
                    ->allowHtml()
                    ->options(function () use ($userModel): array {
                        if (! $userModel || ! class_exists($userModel)) {
                            return [];
                        }

                        $labelColumn = config('filament-comments.mentionable.column.label', 'name');
                        $avatarColumn = config('filament-comments.mentionable.column.avatar', 'profile_photo_path');
                        $emailColumn = config('filament-comments.mentionable.column.email', 'email');

                        return $userModel::query()
                            ->get()
                            ->mapWithKeys(function ($user) use ($labelColumn, $avatarColumn, $emailColumn): array {
                                $name = e($user->{$labelColumn});
                                $email = e($user->{$emailColumn} ?? '');
                                $avatarUrl = static::resolveAvatarUrl($user->{$avatarColumn} ?? null, $user->{$labelColumn});

                                $html = '<div class="flex items-center gap-2">'
                                    . '<img src="' . e($avatarUrl) . '" class="h-6 w-6 rounded-full object-cover" alt="" />'
                                    . '<div class="flex flex-col">'
                                    . '<span class="text-sm font-medium">' . $name . '</span>'
                                    . ($email ? '<span class="text-xs text-gray-500">' . $email . '</span>' : '')
                                    . '</div>'
                                    . '</div>';

                                return [$user->id => $html];
                            })
                            ->toArray();
                    })
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->visible(fn () => $userModel && class_exists($userModel)),
            ])->columns(2),
        ];
    }

    protected static function resolveAvatarUrl(?string $avatarPath, ?string $name): string
    {
        if (! empty($avatarPath)) {
            if (filter_var($avatarPath, FILTER_VALIDATE_URL)) {
                return $avatarPath;
            }
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($avatarPath)) {
                return \Illuminate\Support\Facades\Storage::disk('public')->url($avatarPath);
            }
        }

        return 'https://ui-avatars.com/api/?name=' . urlencode($name ?? 'User') . '&color=FFFFFF&background=6366f1';
    }
}
