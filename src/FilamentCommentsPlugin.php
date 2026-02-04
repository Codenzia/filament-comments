<?php

namespace Codenzia\FilamentComments;

use Codenzia\FilamentComments\Filament\Resources;
use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentCommentsPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-comments';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->discoverResources(in: __DIR__ . '/Filament/Resources', for: 'Codenzia\\FilamentComments\\Filament\\Resources');
    }

    public function boot(Panel $panel): void
    {
        //
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
