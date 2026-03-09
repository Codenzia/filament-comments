<?php

namespace Codenzia\FilamentComments\Filament\Pages;

use Codenzia\FilamentComments\Enums\ChannelType;
use Codenzia\FilamentComments\Models\Comment;
use Codenzia\FilamentComments\Models\CommentChannel;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Contracts\Support\Htmlable;

class DiscussionPage extends Page
{
    protected static \BackedEnum | string | null $navigationIcon = 'heroicon-o-hashtag';

    protected string $view = 'filament-comments::filament.pages.discussion-page';

    protected static bool $shouldRegisterNavigation = false;

    /** Route parameter from URL – do not use for the model. */
    public int | string | null $record = null;

    public ?CommentChannel $channel = null;

    protected static ?string $slug = 'discussion-page';

    public static function getRoutePath(Panel $panel): string
    {
        return '/' . static::getSlug($panel) . '/{record}';
    }

    public function mount(int | string | null $record = null): void
    {
        if ($record === null) {
            abort(404);
        }
        $this->channel = CommentChannel::findOrFail($record);
        if ($this->channel->visibility === 'private' && ! $this->channel->members()->where('users.id', auth()->id())->exists()) {
            abort(404);
        }

        $this->channel->markAsRead();
    }

    public function getTitle(): string | Htmlable
    {
        if ($this->channel?->isDirectMessage()) {
            return $this->channel->dmDisplayName();
        }

        return $this->channel?->name ?? '';
    }

    public function getHeading(): string | Htmlable
    {
        if ($this->channel?->isDirectMessage()) {
            return $this->channel->dmDisplayName();
        }

        return $this->channel?->name ?? '';
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('editChannel')
                ->label('Settings')
                ->iconButton()
                ->tooltip('Settings')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('gray')
                ->slideOver()
                ->visible(fn () => $this->channel->isChannel() && ($this->channel->created_by === auth()->id() || ManageChannelsPage::can('update_channel')))
                ->fillForm(fn (): array => [
                    ...$this->channel->toArray(),
                    'members' => $this->channel->members()->pluck('users.id')->toArray(),
                    'project_id' => $this->channel->project_id,
                ])
                ->form(ManageChannelsPage::getChannelFormSchema())
                ->action(function (array $data): void {
                    $members = $data['members'] ?? [];
                    unset($data['members']);
                    $this->channel->update($data);
                    $this->channel->members()->sync($members);
                    $this->channel->refresh();
                }),
            Action::make('addMembers')
                ->label('Add Members')
                ->iconButton()
                ->tooltip('Add Members')
                ->icon('heroicon-o-user-plus')
                ->color('gray')
                ->modalHeading('Add Members to Conversation')
                ->modalSubmitActionLabel('Add')
                ->visible(fn (): bool => $this->channel->isDirectMessage() && ManageDirectMessagesPage::can('add_member_direct_message'))
                ->form($this->getAddMembersFormSchema())
                ->action(function (array $data): void {
                    $this->channel->channelMembers()->syncWithoutDetaching(
                        array_map('intval', $data['member_ids'])
                    );
                    $this->channel->refresh();
                    $this->dispatch('refresh-sidebar');
                }),
            Action::make('pendingComments')
                ->label(__('Pending Comments'))
                ->iconButton()
                ->tooltip(__('Pending Comments'))
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('gray')
                ->badge(fn () => $this->getPendingCommentsCount())
                ->slideOver()
                ->modalHeading(__('Comments Pending Approval'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(__('Close'))
                ->modalContent(fn () => view('filament-comments::components.pending-comments-modal', [
                    'comments' => $this->getPendingComments(),
                ])),
            Action::make('leaveChannel')
                ->label('Leave Channel')
                ->iconButton()
                ->tooltip('Leave Channel')
                ->icon('heroicon-o-arrow-right-start-on-rectangle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Leave Channel')
                ->modalDescription('Are you sure you want to leave this channel? You will no longer see it in your sidebar.')
                ->modalSubmitActionLabel('Leave')
                ->visible(fn (): bool => $this->channel->isChannel() && $this->channel->members()->where('users.id', auth()->id())->exists() && $this->channel->project_id === null)
                ->action(function (): void {
                    $this->channel->members()->detach(auth()->id());

                    $this->redirect(filament()->getCurrentPanel()->getUrl());
                }),
            Action::make('deleteConversation')
                ->label('Delete Conversation')
                ->iconButton()
                ->tooltip('Delete Conversation')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Delete Conversation')
                ->modalDescription('Are you sure you want to delete this conversation? This action cannot be undone.')
                ->modalSubmitActionLabel('Delete')
                ->visible(fn (): bool => $this->channel->isDirectMessage() && ManageDirectMessagesPage::can('delete_direct_message'))
                ->action(function (): void {
                    $this->channel->channelMembers()->detach(auth()->id());

                    $this->redirect(ManageDirectMessagesPage::getUrl());
                }),
        ];
    }

    protected function getAddMembersFormSchema(): array
    {
        $userModel = config('filament-comments.user_model') ?? config('auth.providers.users.model', \App\Models\User::class);
        $labelColumn = config('filament-comments.mentionable.column.label', 'name');

        $existingMemberIds = $this->channel->channelMembers()->pluck('user_id')->toArray();

        return [
            Select::make('member_ids')
                ->label('Add People')
                ->multiple()
                ->options(fn () => $userModel::query()
                    ->whereNotIn('id', $existingMemberIds)
                    ->pluck($labelColumn, 'id')
                    ->toArray()
                )
                ->searchable()
                ->preload()
                ->required(),
        ];
    }

    private function getPendingCommentsCount(): int
    {
        return Comment::query()
            ->where('channel_id', $this->channel->id)
            ->where('is_approved', 0)
            ->count();
    }

    private function getPendingComments(): \Illuminate\Database\Eloquent\Collection
    {
        return Comment::query()
            ->where('channel_id', $this->channel->id)
            ->where('is_approved', 0)
            ->with('commentator')
            ->get();
    }

    public function approveComment(int $commentId): void
    {
        if ($this->channel->created_by !== auth()->id() && ! ManageChannelsPage::can('update_channel')) {
            abort(403);
        }

        $comment = Comment::findOrFail($commentId);

        if ((int) $comment->channel_id !== (int) $this->channel->id) {
            abort(403);
        }

        $comment->approve();
    }

    public function deleteComment(int $commentId): void
    {
        if ($this->channel->created_by !== auth()->id() && ! ManageChannelsPage::can('delete_channel')) {
            abort(403);
        }

        $comment = Comment::findOrFail($commentId);

        if ((int) $comment->channel_id !== (int) $this->channel->id) {
            abort(403);
        }

        $comment->delete();
    }
}
