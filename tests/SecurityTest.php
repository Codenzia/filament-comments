<?php

use Codenzia\FilamentComments\Models\Comment;
use Codenzia\FilamentComments\Tests\Fixtures\TestPost;
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

    Schema::create('posts', function (Blueprint $table) {
        $table->id();
        $table->string('title');
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

    config()->set('auth.providers.users.model', TestUser::class);
});

it('uses filament-comments config key for user model resolution', function () {
    config()->set('filament-comments.user_model', TestUser::class);

    $user = TestUser::create(['name' => 'Alice', 'email' => 'alice@example.com']);
    $post = TestPost::create(['title' => 'My Post']);
    $this->actingAs($user);

    $post->comment('Test comment');

    $comment = $post->comments()->first();
    $commentator = $comment->commentator;

    expect($commentator)->toBeInstanceOf(TestUser::class)
        ->and($commentator->id)->toBe($user->id);
});

it('falls back to auth config when filament-comments.user_model is null', function () {
    config()->set('filament-comments.user_model', null);

    $user = TestUser::create(['name' => 'Bob', 'email' => 'bob@example.com']);
    $post = TestPost::create(['title' => 'My Post']);
    $this->actingAs($user);

    $post->comment('Test comment');

    $comment = $post->comments()->first();
    $commentator = $comment->commentator;

    expect($commentator)->toBeInstanceOf(TestUser::class);
});

it('uses filament-comments config key for delete_replies_along_comments', function () {
    config()->set('filament-comments.delete_replies_along_comments', true);

    $user = TestUser::create(['name' => 'Alice', 'email' => 'alice@example.com']);
    $post = TestPost::create(['title' => 'My Post']);
    $this->actingAs($user);

    $parent = $post->comment('Parent comment');
    $parent->approve();

    // Create a reply
    $parent->comments()->create([
        'comment' => 'Reply comment',
        'user_id' => $user->id,
        'parent_id' => $parent->id,
        'commentable_id' => $post->id,
        'commentable_type' => get_class($post),
        'is_approved' => true,
    ]);

    expect(Comment::count())->toBe(2);

    $parent->delete();

    expect(Comment::count())->toBe(0);
});

it('can approve and disapprove a comment', function () {
    $user = TestUser::create(['name' => 'Alice', 'email' => 'alice@example.com']);
    $post = TestPost::create(['title' => 'My Post']);
    $this->actingAs($user);

    $comment = $post->comment('Test comment', null, false);
    expect($comment->is_approved)->toBeFalse();

    $comment->approve();
    $comment->refresh();
    expect($comment->is_approved)->toBeTrue();

    $comment->disapprove();
    $comment->refresh();
    expect($comment->is_approved)->toBeFalse();
});

it('returns decoded comment as array for JSON-encoded comments', function () {
    $user = TestUser::create(['name' => 'Alice', 'email' => 'alice@example.com']);
    $post = TestPost::create(['title' => 'My Post']);
    $this->actingAs($user);

    $jsonPayload = json_encode(['question' => 'Test?', 'options' => ['A', 'B'], 'votes' => []]);
    $comment = $post->comment($jsonPayload, 'vote');

    $decoded = $comment->getDecodedComment();
    expect($decoded)->toBeArray()
        ->and($decoded['question'])->toBe('Test?')
        ->and($decoded['options'])->toHaveCount(2);
});

it('returns empty array for non-JSON comment body', function () {
    $user = TestUser::create(['name' => 'Alice', 'email' => 'alice@example.com']);
    $post = TestPost::create(['title' => 'My Post']);
    $this->actingAs($user);

    $comment = $post->comment('Just a plain text comment');
    $decoded = $comment->getDecodedComment();

    expect($decoded)->toBeArray()->toBeEmpty();
});

it('correctly identifies reply comments', function () {
    $user = TestUser::create(['name' => 'Alice', 'email' => 'alice@example.com']);
    $post = TestPost::create(['title' => 'My Post']);
    $this->actingAs($user);

    $parent = $post->comment('Parent');
    expect($parent->isReply())->toBeFalse();

    $reply = Comment::create([
        'comment' => 'Reply',
        'user_id' => $user->id,
        'parent_id' => $parent->id,
        'commentable_id' => $post->id,
        'commentable_type' => get_class($post),
        'is_approved' => true,
    ]);

    expect($reply->isReply())->toBeTrue();
});

it('uses filament-comments config key for comment class', function () {
    // Verify the trait reads from filament-comments.comment_class
    config()->set('filament-comments.comment_class', Comment::class);

    $user = TestUser::create(['name' => 'Alice', 'email' => 'alice@example.com']);
    $post = TestPost::create(['title' => 'My Post']);
    $this->actingAs($user);

    $comment = $post->comment('Test');
    expect($comment)->toBeInstanceOf(Comment::class);
});
