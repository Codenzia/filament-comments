<?php

namespace Codenzia\FilamentComments\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    public $signature = 'filament-comments:install';

    public $description = 'Install Filament Comments';

    public function handle(): int
    {
        $this->info('Installing Filament Comments...');

        $this->info('Publishing configuration...');
        $this->call('vendor:publish', [
            '--tag' => 'codenzia-comments-config',
        ]);

        $this->info('Publishing migrations...');
        $this->call('vendor:publish', [
            '--tag' => 'codenzia-comments-migrations',
        ]);

        if ($this->confirm('Would you like to run the migrations now?', true)) {
            $this->info('Running migrations...');
            $this->call('migrate');
        }

        $this->info('Filament Comments installed successfully!');

        return self::SUCCESS;
    }
}
