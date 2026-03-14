<?php

use Codenzia\FilamentComments\Models\CommentBookmark;
use Codenzia\FilamentComments\Tests\Fixtures\CreatesTestSchema;
use Codenzia\FilamentComments\Tests\Fixtures\TestUser;

uses(CreatesTestSchema::class);

it('can bookmark a comment', function () {
    [$user, $post] = $this->createUserAndPost();

    $comment = $post->comment('Bookmark me');
    $comment->approve();

    CommentBookmark::create([
        'user_id' => $user->id,
        'comment_id' => $comment->id,
    ]);

    expect($comment->isBookmarkedBy($user->id))->toBeTrue();
});

it('can unbookmark a comment', function () {
    [$user, $post] = $this->createUserAndPost();

    $comment = $post->comment('Bookmark then remove');
    $comment->approve();

    $bookmark = CommentBookmark::create([
        'user_id' => $user->id,
        'comment_id' => $comment->id,
    ]);

    expect($comment->isBookmarkedBy($user->id))->toBeTrue();

    $bookmark->delete();

    // Refresh to clear relationship cache
    $comment->refresh();

    expect($comment->isBookmarkedBy($user->id))->toBeFalse();
});

it('bookmarks are per-user', function () {
    [$user, $post] = $this->createUserAndPost();

    $user2 = TestUser::create(['name' => 'Jane', 'email' => 'jane@example.com']);

    $comment = $post->comment('Per-user bookmark');
    $comment->approve();

    CommentBookmark::create([
        'user_id' => $user->id,
        'comment_id' => $comment->id,
    ]);

    expect($comment->isBookmarkedBy($user->id))->toBeTrue();
    expect($comment->isBookmarkedBy($user2->id))->toBeFalse();
});

it('duplicate bookmark is prevented', function () {
    [$user, $post] = $this->createUserAndPost();

    $comment = $post->comment('No duplicates');
    $comment->approve();

    CommentBookmark::create([
        'user_id' => $user->id,
        'comment_id' => $comment->id,
    ]);

    expect(fn () => CommentBookmark::create([
        'user_id' => $user->id,
        'comment_id' => $comment->id,
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

it('isBookmarkedBy returns correct boolean', function () {
    [$user, $post] = $this->createUserAndPost();

    $comment = $post->comment('Check bookmark');
    $comment->approve();

    expect($comment->isBookmarkedBy($user->id))->toBeFalse();

    CommentBookmark::create([
        'user_id' => $user->id,
        'comment_id' => $comment->id,
    ]);

    // Need to refresh the relation
    $comment->refresh();

    expect($comment->isBookmarkedBy($user->id))->toBeTrue();
});
