<?php

use Codenzia\FilamentComments\Models\CommentChannel;
use Codenzia\FilamentComments\Tests\Fixtures\TestUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    // Point auth and plugin config to TestUser so relationships resolve correctly
    config()->set('auth.providers.users.model', TestUser::class);

    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->timestamps();
    });

    Schema::create('comment_channels', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('slug')->unique();
        $table->text('description')->nullable();
        $table->string('icon')->nullable();
        $table->string('visibility')->default('public');
        $table->boolean('show_sidebar')->default(true);
        $table->unsignedBigInteger('project_id')->nullable();
        $table->unsignedBigInteger('created_by')->nullable();
        $table->timestamps();
    });

    Schema::create('comment_channel_members', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('channel_id');
        $table->unsignedBigInteger('user_id');
        $table->timestamps();
    });
});

it('has a createdBy relationship', function () {
    $user = TestUser::create(['name' => 'Alice', 'email' => 'alice@example.com']);

    $this->actingAs($user);

    $channel = CommentChannel::create([
        'name' => 'General',
        'slug' => 'general',
    ]);

    expect($channel->createdBy)->toBeInstanceOf(TestUser::class)
        ->and($channel->createdBy->id)->toBe($user->id);
});

it('sets created_by automatically from auth user', function () {
    $user = TestUser::create(['name' => 'Bob', 'email' => 'bob@example.com']);

    $this->actingAs($user);

    $channel = CommentChannel::create([
        'name' => 'Random',
        'slug' => 'random',
    ]);

    expect($channel->created_by)->toBe($user->id);
});

it('does not overwrite explicit created_by', function () {
    $alice = TestUser::create(['name' => 'Alice', 'email' => 'alice@example.com']);
    $bob = TestUser::create(['name' => 'Bob', 'email' => 'bob@example.com']);

    $this->actingAs($alice);

    $channel = CommentChannel::create([
        'name' => 'Engineering',
        'slug' => 'engineering',
        'created_by' => $bob->id,
    ]);

    expect($channel->created_by)->toBe($bob->id)
        ->and($channel->createdBy->name)->toBe('Bob');
});

it('allows null created_by when no auth user', function () {
    $channel = CommentChannel::create([
        'name' => 'System',
        'slug' => 'system',
    ]);

    expect($channel->created_by)->toBeNull()
        ->and($channel->createdBy)->toBeNull();
});

it('has comments relationship', function () {
    $user = TestUser::create(['name' => 'Alice', 'email' => 'alice@example.com']);

    $this->actingAs($user);

    $channel = CommentChannel::create([
        'name' => 'General',
        'slug' => 'general',
    ]);

    expect($channel->comments()->getRelated())->toBeInstanceOf(\Codenzia\FilamentComments\Models\Comment::class);
});

it('auto-attaches creator as channel member on create', function () {
    $user = TestUser::create(['name' => 'Alice', 'email' => 'alice@example.com']);

    $this->actingAs($user);

    $channel = CommentChannel::create([
        'name' => 'General',
        'slug' => 'general',
    ]);

    // Boot hook auto-attaches the authenticated user as a member
    expect($channel->channelMembers)->toHaveCount(1)
        ->and($channel->channelMembers->first()->id)->toBe($user->id);
});

it('can attach additional members', function () {
    $alice = TestUser::create(['name' => 'Alice', 'email' => 'alice@example.com']);
    $bob = TestUser::create(['name' => 'Bob', 'email' => 'bob@example.com']);

    $this->actingAs($alice);

    $channel = CommentChannel::create([
        'name' => 'General',
        'slug' => 'general',
    ]);

    $channel->channelMembers()->attach($bob->id);

    expect($channel->fresh()->channelMembers)->toHaveCount(2);
});
