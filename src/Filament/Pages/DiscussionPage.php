<?php

namespace Codenzia\FilamentComments\Filament\Pages;

use Codenzia\FilamentComments\Models\CommentChannel;
use Filament\Navigation\NavigationItem;
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

    public static function getNavigationItems(): array
    {
        $group = static::getNavigationGroup();
        $items = [];

        // Add "Manage Channels" link
        $items[] = NavigationItem::make('Manage Channels')
            ->group($group)
            ->icon('heroicon-o-cog-6-tooth')
            ->url(ManageChannelsPage::getUrl())
            ->isActiveWhen(fn () => request()->routeIs(ManageChannelsPage::getRouteName()));

        try {
            // Fetch channels to list them in navigation
            $channels = CommentChannel::all();

            foreach ($channels as $channel) {
                $items[] = NavigationItem::make($channel->name)
                    ->group($group)
                    ->icon('heroicon-o-hashtag')
                    ->url(static::getUrl(['record' => $channel->id]))
                    ->isActiveWhen(fn () => request()->routeIs(static::getRouteName()) && (string) request()->route('record') === (string) $channel->id);
            }
        } catch (\Exception $e) {
            // Fallback if table doesn't exist
        }

        return $items;
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
}
