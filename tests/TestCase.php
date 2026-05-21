<?php

namespace Codenzia\FilamentComments\Tests;

use Codenzia\FilamentComments\FilamentCommentsServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    /**
     * Livewire is listed explicitly because its "livewire.finder" binding
     * doesn't survive Testbench's package:discover alone. Filament's own
     * providers + Blade/Icons providers are auto-discovered.
     */
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            FilamentCommentsServiceProvider::class,
        ];
    }
}
