<?php

namespace Codenzia\FilamentComments\Filament\Pages;

use Codenzia\FilamentComments\Models\CommentChannel;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Navigation\NavigationItem;

class DiscussionPage extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-hashtag';

    protected string $view = 'codenzia-comments::filament.pages.discussion-page';

    protected static bool $shouldRegisterNavigation = false;

    public CommentChannel $record;

    public static function getNavigationItems(): array
    {
        $group = static::getNavigationGroup();
        $items = [];

        // Add "Manage Channels" link
        $items[] = NavigationItem::make('Manage Channels')
            ->group($group)
            ->icon('heroicon-o-cog-6-tooth')
            ->url(static::getUrl('index'))
            ->isActiveWhen(fn () => request()->routeIs(static::getRouteBaseName() . '.index') || request()->routeIs(static::getRouteBaseName() . '.create') || request()->routeIs(static::getRouteBaseName() . '.edit'));

        try {
            // Fetch channels to list them in navigation
            $channels = CommentChannel::all();

            foreach ($channels as $channel) {
                $items[] = NavigationItem::make($channel->name)
                    ->group($group)
                    ->icon('heroicon-o-hashtag')
                    ->url(static::getUrl('view-comments', ['record' => $channel]))
                    ->isActiveWhen(fn () => request()->routeIs(static::getRouteBaseName() . '.view-comments') && request()->route('record') == $channel->id);
            }
        } catch (\Exception $e) {
            // Fallback if table doesn't exist
        }

        return $items;
    }

    public function mount(int | string $record): void
    {
        $this->record = CommentChannel::findOrFail($record);
    }

    public function getTitle(): string | Htmlable
    {
        return $this->record->name;
    }

    public function getHeading(): string | Htmlable
    {
        return $this->record->name;
    }
}
