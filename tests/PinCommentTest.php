<?php

use Codenzia\FilamentComments\Models\Comment;
use Codenzia\FilamentComments\Tests\Fixtures\CreatesTestSchema;

uses(CreatesTestSchema::class);

it('can pin a comment', function () {
    [$user, $post] = $this->createUserAndPost();

    $comment = $post->comment('Pin me!');
    $comment->approve();

    $comment->pin();

    expect($comment->fresh()->is_pinned)->toBeTrue();
});

it('unpins previous comment when pinning a new one', function () {
    [$user, $post] = $this->createUserAndPost();

    $comment1 = $post->comment('First pinned');
    $comment1->approve();
    $comment1->pin();

    $comment2 = $post->comment('Second pinned');
    $comment2->approve();
    $comment2->pin();

    expect($comment1->fresh()->is_pinned)->toBeFalse();
    expect($comment2->fresh()->is_pinned)->toBeTrue();
});

it('can unpin a comment', function () {
    [$user, $post] = $this->createUserAndPost();

    $comment = $post->comment('Unpin me');
    $comment->approve();
    $comment->pin();

    expect($comment->fresh()->is_pinned)->toBeTrue();

    $comment->unpin();

    expect($comment->fresh()->is_pinned)->toBeFalse();
});

it('only one pinned comment per commentable', function () {
    [$user, $post] = $this->createUserAndPost();

    $comments = collect();
    for ($i = 0; $i < 3; $i++) {
        $c = $post->comment("Comment {$i}");
        $c->approve();
        $comments->push($c);
    }

    // Pin each one sequentially
    foreach ($comments as $c) {
        $c->pin();
    }

    $pinnedCount = Comment::where('commentable_type', get_class($post))
        ->where('commentable_id', $post->id)
        ->where('is_pinned', true)
        ->count();

    expect($pinnedCount)->toBe(1);
});

it('pinned comments appear first via scope', function () {
    [$user, $post] = $this->createUserAndPost();

    $c1 = $post->comment('Normal comment');
    $c1->approve();

    $c2 = $post->comment('Pinned comment');
    $c2->approve();
    $c2->pin();

    $pinned = Comment::pinned()->get();

    expect($pinned)->toHaveCount(1);
    expect($pinned->first()->id)->toBe($c2->id);
});
