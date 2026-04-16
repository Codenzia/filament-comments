<?php

namespace Codenzia\FilamentComments;

use Codenzia\FilamentComments\Filament\Pages\DiscussionPage;
use Codenzia\FilamentComments\Filament\Pages\ManageChannelsPage;
use Codenzia\FilamentComments\Filament\Pages\ManageDirectMessagesPage;
use Codenzia\FilamentComments\Models\CommentChannel;
use Filament\Contracts\Plugin;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Illuminate\Support\Str;

class FilamentCommentsPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-comments';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->pages([
                DiscussionPage::class,
                ManageChannelsPage::class,
                ManageDirectMessagesPage::class,
            ]);

        $channelsGroup = config('filament-comments.navigation_groups.channels', 'Channels');
        $dmGroup = config('filament-comments.navigation_groups.direct_messages', 'Direct Messages');

        $panel->navigationGroups([
            $channelsGroup => NavigationGroup::make($channelsGroup),
            $dmGroup => NavigationGroup::make($dmGroup),
        ]);
    }

    public function boot(Panel $panel): void
    {
        $panel->navigationItems($this->getNavigationItems());
    }

    protected function getNavigationItems(): array
    {
        $items = [];
        $channelsGroup = config('filament-comments.navigation_groups.channels', 'Channels');
        $dmGroup = config('filament-comments.navigation_groups.direct_messages', 'Direct Messages');
        $channelsLimit = config('filament-comments.sidebar_limit.channels');
        $dmLimit = config('filament-comments.sidebar_limit.direct_messages');

        // --- Channels group ---
        $items[] = NavigationItem::make('New Channel')
            ->group($channelsGroup)
            ->icon('heroicon-o-plus-circle')
            ->url(ManageChannelsPage::getUrl() . '?create=1')
            ->sort(0)
            ->visible(fn (): bool => static::checkPermission('create_channel'));

        $items[] = NavigationItem::make('All Channels')
            ->group($channelsGroup)
            ->icon('heroicon-o-cog-6-tooth')
            ->url(ManageChannelsPage::getUrl())
            ->sort(1)
            ->isActiveWhen(fn () => request()->routeIs(ManageChannelsPage::getRouteName()))
            ->visible(fn (): bool => static::checkPermission('view_channel'));

        // --- Direct Messages group ---
        $items[] = NavigationItem::make('New Conversation')
            ->group($dmGroup)
            ->icon('heroicon-o-plus-circle')
            ->url(ManageDirectMessagesPage::getUrl() . '?create=1')
            ->sort(0)
            ->visible(fn (): bool => static::checkPermission('create_direct_message'));

        $items[] = NavigationItem::make('All Conversations')
            ->group($dmGroup)
            ->icon('heroicon-o-chat-bubble-left-right')
            ->url(ManageDirectMessagesPage::getUrl())
            ->sort(1)
            ->isActiveWhen(fn () => request()->routeIs(ManageDirectMessagesPage::getRouteName()))
            ->visible(fn (): bool => static::checkPermission('view_direct_message'));

        $channelsQuery = CommentChannel::query()
            ->channels()
            ->where('show_sidebar', true)
            ->with('channelMembers')
            ->latest('updated_at');

        if ($channelsLimit) {
            $channelsQuery->take($channelsLimit);
        }

        $channels = $channelsQuery->get();

        $sortIndex = 2;
        foreach ($channels as $channel) {
            $unread = $channel->unreadCount();
            $items[] = NavigationItem::make($channel->name)
                ->group($channelsGroup)
                ->icon($channel->icon ?: 'heroicon-o-hashtag')
                ->url(DiscussionPage::getUrl(['record' => $channel->id]))
                ->sort($sortIndex++)
                ->badge($unread > 0 ? $unread : null)
                ->isActiveWhen(fn () => request()->routeIs(DiscussionPage::getRouteName()) && request()->route('record') == $channel->id)
                ->visible(function () use ($channel): bool {
                    if ($channel->visibility === 'public') {
                        return true;
                    }

                    return $channel->channelMembers->contains('id', auth()->id());
                });
        }

        $dmQuery = CommentChannel::query()
            ->directMessages()
            ->where('show_sidebar', true)
            ->with('channelMembers')
            ->latest('updated_at');

        if ($dmLimit) {
            $dmQuery->take($dmLimit);
        }

        $dmChannels = $dmQuery->get();

        $dmSort = 2;
        foreach ($dmChannels as $dm) {
            $fullName = $dm->dmDisplayName();
            $unread = $dm->unreadCount();
            $items[] = NavigationItem::make(Str::limit($fullName, 20))
                ->group($dmGroup)
                ->icon($dm->channelMembers->count() > 2 ? 'heroicon-o-user-group' : 'heroicon-o-user')
                ->url(DiscussionPage::getUrl(['record' => $dm->id]))
                ->sort($dmSort++)
                ->badge($unread > 0 ? $unread : null)
                ->extraAttributes(['title' => $fullName])
                ->isActiveWhen(fn () => request()->routeIs(DiscussionPage::getRouteName()) && request()->route('record') == $dm->id)
                ->visible(fn (): bool => $dm->channelMembers->contains('id', auth()->id()));
        }

        return $items;
    }

    /**
     * Check if the current user has the given comment-channel permission.
     * If the config value is null, any authenticated user is allowed.
     */
    protected static function checkPermission(string $ability): bool
    {
        $permission = config("filament-comments.permissions.{$ability}");

        if ($permission === null) {
            return true;
        }

        return auth()->user()?->can($permission) ?? false;
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
