<?php

use Codenzia\FilamentComments\Tests\Fixtures\TestPost;
use Codenzia\FilamentComments\Tests\Fixtures\TestUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

it('can render comments component', function () {
    // Livewire v3's validation support reads $errors during render; in a
    // bare Blade::render context (no HTTP middleware), the ViewErrorBag
    // isn't shared by default. Pre-share an empty one so the component
    // doesn't throw when Livewire tries to merge validation errors.
    View::share('errors', (new ViewErrorBag)->put('default', new MessageBag));

    Schema::create('posts', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->timestamps();
    });

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

    Schema::create('comments', function (Blueprint $table) {
        $table->id();
        $table->nullableMorphs('commentable');
        $table->text('comment');
        $table->string('type')->nullable();
        $table->boolean('is_approved')->default(false);
        $table->unsignedBigInteger('user_id')->nullable();
        $table->unsignedBigInteger('channel_id')->nullable();
        $table->unsignedBigInteger('parent_id')->nullable();
        $table->foreign('parent_id')->references('id')->on('comments')->onDelete('cascade');
        $table->foreign('channel_id')->references('id')->on('comment_channels')->onDelete('set null');
        $table->timestamps();
    });

    Schema::create('comment_watches', function (Blueprint $table) {
        $table->id();
        $table->morphs('watchable');
        $table->unsignedBigInteger('user_id');
        $table->timestamps();
    });

    $user = TestUser::create(['name' => 'John', 'email' => 'john@example.com']);
    $post = TestPost::create(['title' => 'My Post']);

    $this->actingAs($user);

    $post->comment('This is a test comment');
    $post->comments->first()->approve();

    $view = Blade::render('<x-filament-comments::comment :record="$post" />', ['post' => $post]);

    // The Blade component renders a Livewire tag; actual content is rendered by Livewire at runtime
    expect($view)->toContain('livewire:filament-comments::comments');
});
