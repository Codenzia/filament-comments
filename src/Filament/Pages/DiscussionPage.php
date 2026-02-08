<?php

namespace Codenzia\FilamentComments\Filament\Pages;

use Codenzia\FilamentComments\Models\CommentChannel;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Contracts\Support\Htmlable;

class DiscussionPage extends Page
{
    protected static \BackedEnum | string | null $navigationIcon = 'heroicon-o-hashtag';

    protected string $view = 'codenzia-comments::filament.pages.discussion-page';

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
    }

    public function getTitle(): string | Htmlable
    {
        return $this->channel?->name ?? '';
    }

    public function getHeading(): string | Htmlable
    {
        return $this->channel?->name ?? '';
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('leaveChannel')
                ->label('Leave Channel')
                ->icon('heroicon-o-arrow-right-start-on-rectangle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Leave Channel')
                ->modalDescription('Are you sure you want to leave this channel? You will no longer see it in your sidebar.')
                ->modalSubmitActionLabel('Leave')
                ->visible(fn (): bool => $this->channel->members()->where('users.id', auth()->id())->exists())
                ->action(function (): void {
                    $this->channel->members()->detach(auth()->id());

                    $this->redirect(filament()->getCurrentPanel()->getUrl());
                }),
            Action::make('editChannel')
                ->label('Settings')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('gray')
                ->slideOver()
                ->visible(fn () => $this->channel->created_by === auth()->id())
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
        ];
    }
}
