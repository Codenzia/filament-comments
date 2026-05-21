<?php

namespace Codenzia\FilamentComments\Tests;

use BladeUI\Icons\BladeIconsServiceProvider;
use Codenzia\FilamentComments\FilamentCommentsServiceProvider;
use Filament\Support\SupportServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    /**
     * Livewire is listed explicitly because its "livewire.finder" binding
     * doesn't survive Testbench's package:discover alone. Blade Icons +
     * Filament Support are also listed so tests that render
     * <x-filament::icon> in views can resolve the view component.
     */
    protected function getPackageProviders($app): array
    {
        return [
            BladeIconsServiceProvider::class,
            LivewireServiceProvider::class,
            SupportServiceProvider::class,
            FilamentCommentsServiceProvider::class,
        ];
    }
}
