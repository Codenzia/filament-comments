<?php

namespace Codenzia\FilamentComments\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;

class InstallCommand extends Command
{
    public $signature = 'filament-comments:install';

    public $description = 'Install Filament Comments';

    public function handle(): int
    {
        $this->info('Installing Filament Comments...');

        $this->info('Publishing configuration...');
        $this->call('vendor:publish', [
            '--tag' => 'filament-comments-config',
        ]);

        $this->info('Publishing migrations...');
        $this->call('vendor:publish', [
            '--tag' => 'filament-comments-migrations',
        ]);

        if ($this->confirm('Would you like to run the migrations now?', true)) {
            $this->info('Running migrations...');
            $this->call('migrate');
        }

        if (! class_exists(Permission::class)) {
            $this->warn('   spatie/laravel-permission is not installed — skipping permission seeding.');
            $this->line('   Install it (or filament-shield, which brings it in) and re-run this command');
            $this->line('   to seed the comment permissions.');
        } elseif ($this->confirm('Would you like to seed the comment permissions now?', true)) {
            $this->info('Seeding permissions...');
            static::seedPermissions();
            $this->info('   Permissions created. Assign them to roles via Shield or manually.');
        }

        $this->info('Filament Comments installed successfully!');

        return self::SUCCESS;
    }

    /**
     * Create Spatie Permission records for all configured comment permissions.
     *
     * Safe to call multiple times — uses firstOrCreate. Returns silently if
     * spatie/laravel-permission is not installed (the package is suggested,
     * not required — Shield brings it in for apps that need moderation).
     * Can be called from seeders: InstallCommand::seedPermissions()
     */
    public static function seedPermissions(): void
    {
        if (! class_exists(Permission::class)) {
            return;
        }

        $permissionClass = config('permission.models.permission', Permission::class);
        $guardName = config('auth.defaults.guard', 'web');

        foreach (config('filament-comments.permissions', []) as $permissionName) {
            if ($permissionName !== null) {
                $permissionClass::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => $guardName,
                ]);
            }
        }
    }
}
