<?php

use Codenzia\FilamentComments\Tests\Fixtures\TestUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->timestamps();
    });
});

/**
 * Helper that mirrors ManageChannelsPage::can() logic
 * to test without loading the Filament Page class.
 */
function canAbility(string $ability): bool
{
    $permission = config("filament-comments.permissions.{$ability}");

    if ($permission === null) {
        return true;
    }

    return auth()->user()?->can($permission) ?? false;
}

it('allows any authenticated user when permission is null', function () {
    config()->set('filament-comments.permissions.create_channel', null);

    $user = TestUser::create(['name' => 'Alice', 'email' => 'alice@example.com']);
    $this->actingAs($user);

    expect(canAbility('create_channel'))->toBeTrue();
});

it('allows when permission config key does not exist', function () {
    config()->set('filament-comments.permissions', []);

    $user = TestUser::create(['name' => 'Alice', 'email' => 'alice@example.com']);
    $this->actingAs($user);

    expect(canAbility('nonexistent_ability'))->toBeTrue();
});

it('denies unauthenticated users when permission is set', function () {
    config()->set('filament-comments.permissions.create_channel', 'create_comment_channel');

    expect(canAbility('create_channel'))->toBeFalse();
});

it('denies authenticated user without spatie permission', function () {
    config()->set('filament-comments.permissions.delete_channel', 'delete_comment_channel');

    $user = TestUser::create(['name' => 'Bob', 'email' => 'bob@example.com']);
    $this->actingAs($user);

    // TestUser does not use HasRoles/HasPermissions, so can() returns false
    expect(canAbility('delete_channel'))->toBeFalse();
});

it('loads permissions from config correctly', function () {
    config()->set('filament-comments.permissions', [
        'create_channel' => 'create_comment_channel',
        'update_channel' => null,
        'delete_channel' => 'delete_comment_channel',
        'view_channel' => null,
    ]);

    $user = TestUser::create(['name' => 'Alice', 'email' => 'alice@example.com']);
    $this->actingAs($user);

    // null permissions are open to all
    expect(canAbility('update_channel'))->toBeTrue()
        ->and(canAbility('view_channel'))->toBeTrue();

    // string permissions require the actual Spatie permission
    expect(canAbility('create_channel'))->toBeFalse()
        ->and(canAbility('delete_channel'))->toBeFalse();
});
