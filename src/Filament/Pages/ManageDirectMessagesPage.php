<?php

namespace Codenzia\FilamentComments\Filament\Pages;

use Codenzia\FilamentComments\Models\CommentChannel;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ManageDirectMessagesPage extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static \BackedEnum | string | null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected string $view = 'filament-comments::filament.pages.manage-direct-messages-page';

    protected static ?string $slug = 'direct-messages';

    protected static bool $shouldRegisterNavigation = false;

    public bool $shouldOpenCreateModal = false;

    public function mount(): void
    {
        abort_unless(static::can('view_direct_message'), 403);

        if (request()->boolean('create')) {
            $this->shouldOpenCreateModal = true;
        }
    }

    public static function getNavigationGroup(): ?string
    {
        return config('filament-comments.navigation_groups.direct_messages', 'Direct Messages');
    }

    public static function getNavigationLabel(): string
    {
        return config('filament-comments.navigation_groups.direct_messages', 'Direct Messages');
    }

    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return config('filament-comments.navigation_groups.direct_messages', 'Direct Messages');
    }

    public function getHeading(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return config('filament-comments.navigation_groups.direct_messages', 'Direct Messages');
    }

    public function getSubheading(): string | \Illuminate\Contracts\Support\Htmlable | null
    {
        return 'View and manage all your conversations';
    }

    /**
     * Check if the current user has the given permission.
     * If the config value is null, any authenticated user is allowed.
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
            ->query(
                CommentChannel::query()
                    ->directMessages()
                    ->whereHas('channelMembers', fn ($q) => $q->where('user_id', auth()->id()))
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Conversation')
                    ->formatStateUsing(fn (CommentChannel $record): string => $record->dmDisplayName())
                    ->icon('heroicon-o-chat-bubble-left-right'),
                TextColumn::make('channel_members_count')
                    ->counts('channelMembers')
                    ->label('Members')
                    ->sortable(),
                TextColumn::make('comments_count')
                    ->counts('comments')
                    ->label('Messages')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Last Activity')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->actions([
                ActionGroup::make([
                    EditAction::make('addMembers')
                        ->label('Add Members')
                        ->icon('heroicon-o-user-plus')
                        ->modalHeading('Add Members to Conversation')
                        ->modalSubmitActionLabel('Add')
                        ->visible(fn (): bool => static::can('add_member_direct_message'))
                        ->form($this->getAddMembersFormSchema())
                        ->fillForm(fn (CommentChannel $record): array => [
                            'member_ids' => [],
                        ])
                        ->using(function (CommentChannel $record, array $data): void {
                            $record->channelMembers()->syncWithoutDetaching(
                                array_map('intval', $data['member_ids'])
                            );
                            $this->dispatch('refresh-sidebar');
                        }),
                    DeleteAction::make()
                        ->label('Delete')
                        ->requiresConfirmation()
                        ->modalHeading('Delete Conversation')
                        ->modalDescription('Are you sure you want to delete this conversation? This action cannot be undone.')
                        ->modalSubmitActionLabel('Delete')
                        ->visible(fn (): bool => static::can('delete_direct_message'))
                        ->action(function (CommentChannel $record): void {
                            // Just remove the current user from the DM, don't delete the channel
                            $record->channelMembers()->detach(auth()->id());
                            $this->dispatch('refresh-sidebar');
                        }),
                ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('New Conversation')
                    ->modalHeading('Start a New Conversation')
                    ->modalSubmitActionLabel('Start')
                    ->createAnother(false)
                    ->icon('heroicon-o-plus')
                    ->visible(fn (): bool => static::can('create_direct_message'))
                    ->form($this->getNewDmFormSchema())
                    ->using(function (array $data): CommentChannel {
                        $userIds = array_map('intval', $data['user_ids']);
                        $userIds[] = auth()->id();

                        $channel = CommentChannel::findOrCreateDirectMessage($userIds);

                        $this->redirect(DiscussionPage::getUrl(['record' => $channel->id]));

                        return $channel;
                    }),
            ])
            ->emptyStateHeading('No conversations')
            ->emptyStateDescription('Start a conversation to get started.')
            ->emptyStateIcon('heroicon-o-chat-bubble-left-right')
            ->recordUrl(fn (CommentChannel $record): string => DiscussionPage::getUrl(['record' => $record->id]));
    }

    protected function getAddMembersFormSchema(): array
    {
        $userModel = config('filament-comments.user_model') ?? config('auth.providers.users.model', \App\Models\User::class);
        $labelColumn = config('filament-comments.mentionable.column.label', 'name');
        $avatarColumn = config('filament-comments.mentionable.column.avatar', 'profile_photo_path');
        $emailColumn = config('filament-comments.mentionable.column.email', 'email');

        return [
            Select::make('member_ids')
                ->label('Add People')
                ->multiple()
                ->allowHtml()
                ->options(function () use ($userModel, $labelColumn, $avatarColumn, $emailColumn): array {
                    return $userModel::query()
                        ->where('id', '!=', auth()->id())
                        ->get()
                        ->mapWithKeys(function ($user) use ($labelColumn, $avatarColumn, $emailColumn): array {
                            $name = e($user->{$labelColumn});
                            $email = e($user->{$emailColumn} ?? '');
                            $avatarPath = $user->{$avatarColumn} ?? null;
                            $avatarUrl = $this->getUserAvatarUrl($avatarPath, $user->{$labelColumn});

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
                ->searchable()
                ->preload()
                ->required(),
        ];
    }

    protected function getNewDmFormSchema(): array
    {
        $userModel = config('filament-comments.user_model') ?? config('auth.providers.users.model', \App\Models\User::class);
        $labelColumn = config('filament-comments.mentionable.column.label', 'name');
        $avatarColumn = config('filament-comments.mentionable.column.avatar', 'profile_photo_path');
        $emailColumn = config('filament-comments.mentionable.column.email', 'email');

        return [
            Select::make('user_ids')
                ->label('To')
                ->multiple()
                ->allowHtml()
                ->options(function () use ($userModel, $labelColumn, $avatarColumn, $emailColumn): array {
                    return $userModel::query()
                        ->where('id', '!=', auth()->id())
                        ->get()
                        ->mapWithKeys(function ($user) use ($labelColumn, $avatarColumn, $emailColumn): array {
                            $name = e($user->{$labelColumn});
                            $email = e($user->{$emailColumn} ?? '');
                            $avatarPath = $user->{$avatarColumn} ?? null;
                            $avatarUrl = $this->getUserAvatarUrl($avatarPath, $user->{$labelColumn});

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
                ->searchable()
                ->preload()
                ->required(),
        ];
    }

    protected function getUserAvatarUrl(?string $avatarPath, ?string $name): string
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
