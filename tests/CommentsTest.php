<?php

use Codenzia\FilamentComments\Tests\Fixtures\TestPost;
use Codenzia\FilamentComments\Tests\Fixtures\TestUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

it('can add a comment', function () {
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

    $user = TestUser::create(['name' => 'John', 'email' => 'john@example.com']);
    $post = TestPost::create(['title' => 'My Post']);

    $this->actingAs($user);

    $post->comment('This is a test comment');

    expect($post->comments)->toHaveCount(1);
    expect($post->comments->first()->comment)->toBe('This is a test comment');
    expect($post->comments->first()->user_id)->toBe($user->id);
});
