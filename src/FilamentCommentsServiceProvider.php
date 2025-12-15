<?php

namespace Codenzia\FilamentComments;

use Codenzia\FilamentComments\Commands\FilamentCommentsCommand;
use Codenzia\FilamentComments\Testing\TestsFilamentComments;
use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Filesystem\Filesystem;
use Livewire\Features\SupportTesting\Testable;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

use Livewire\Livewire;
use Codenzia\FilamentComments\Livewire\CommentsComponent;
use Codenzia\FilamentComments\Livewire\CommentItem;

class FilamentCommentsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'codenzia-comments';

    public static string $viewNamespace = 'codenzia-comments';

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('codenzia/codenzia-comments');
            });

        $configFileName = $package->shortName();

        if (file_exists($package->basePath("/../config/{$configFileName}.php"))) {
            $package->hasConfigFile();
        }

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations());
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void {}

    public function packageBooted(): void
    {
        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        // Handle Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/codenzia-comments/{$file->getFilename()}"),
                ], 'codenzia-comments-stubs');
            }
        }

        // Livewire Component Registration
        Livewire::component('codenzia-comments::comments', CommentsComponent::class);
        Livewire::component('codenzia-comments::comment-item', CommentItem::class);

        // Testing
        Testable::mixin(new TestsFilamentComments);
    }

    protected function getAssetPackageName(): ?string
    {
        return 'codenzia/codenzia-comments';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            Css::make('codenzia-comments-styles', __DIR__ . '/../resources/dist/codenzia-comments.css'),
            Js::make('codenzia-comments-scripts', __DIR__ . '/../resources/dist/codenzia-comments.js'),
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            FilamentCommentsCommand::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getRoutes(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'create_filament_comments_table',
        ];
    }
}
