<?php

namespace Codenzia\FilamentComments;

use Codenzia\FilamentComments\Filament\Pages\ApproveCommentsPage;
use Codenzia\FilamentComments\Filament\Pages\DiscussionPage;
use Codenzia\FilamentComments\Filament\Pages\ManageChannelsPage;
use Codenzia\FilamentComments\Models\Comment;
use Codenzia\FilamentComments\Models\CommentChannel;
use Filament\Contracts\Plugin;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Illuminate\Support\Facades\Route;

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
                ApproveCommentsPage::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        $panel->navigationItems($this->getNavigationItems());

        // Explicitly register the parameterized route for DiscussionPage if needed,
        // though Filament usually handles this via getRoutePart if it were a resource.
        // For standalone pages with parameters, we often need to ensure the route exists.
    }

    protected function getNavigationItems(): array
    {
        $items = [];
        $group = config('codenzia-comments.navigation_group', 'Group Discussions');

        $items[] = NavigationItem::make('All')
            ->group($group)
            ->icon('heroicon-o-cog-6-tooth')
            ->url(ManageChannelsPage::getUrl())
            ->isActiveWhen(fn () => request()->routeIs(ManageChannelsPage::getRouteName()));

        $items[] = NavigationItem::make('Approvals')
            ->group($group)
            ->icon('heroicon-o-check-circle')
            ->url(ApproveCommentsPage::getUrl())
            ->badge(fn () => Comment::where('is_approved', false)->count())
            ->isActiveWhen(fn () => request()->routeIs(ApproveCommentsPage::getRouteName()));

        try {
            $channels = CommentChannel::where('show_sidebar', true)->get();
            foreach ($channels as $channel) {
                $items[] = NavigationItem::make($channel->name)
                    ->group($group)
                    ->icon($channel->icon ?: 'heroicon-o-hashtag')
                    ->url(DiscussionPage::getUrl(['record' => $channel->id]))
                    ->isActiveWhen(fn () => request()->routeIs(DiscussionPage::getRouteName()) && request()->route('record') == $channel->id)
                    ->visible(function () use ($channel): bool {
                        if ($channel->visibility === 'public') {
                            return true;
                        }

                        return $channel->members()->where('users.id', auth()->id())->exists();
                    });
            }
        } catch (\Exception $e) {
            // Table might not exist yet
        }

        return $items;
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
