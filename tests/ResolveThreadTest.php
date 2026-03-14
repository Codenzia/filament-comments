<?php

use Codenzia\FilamentComments\Models\Comment;
use Codenzia\FilamentComments\Tests\Fixtures\CreatesTestSchema;
use Codenzia\FilamentComments\Tests\Fixtures\TestUser;

uses(CreatesTestSchema::class);

it('can resolve a root comment thread', function () {
    [$user, $post] = $this->createUserAndPost();

    $comment = $post->comment('Resolve me');
    $comment->approve();

    $comment->resolve();

    expect($comment->fresh()->is_resolved)->toBeTrue();
    expect($comment->fresh()->resolved_by)->toBe($user->id);
});

it('cannot resolve a reply comment', function () {
    [$user, $post] = $this->createUserAndPost();

    $parent = $post->comment('Parent');
    $parent->approve();

    $reply = $parent->replies()->create([
        'comment' => 'I am a reply',
        'user_id' => $user->id,
        'commentable_id' => $post->id,
        'commentable_type' => get_class($post),
        'is_approved' => true,
        'parent_id' => $parent->id,
    ]);

    $reply->resolve();

    // Reply should not be resolved (isReply returns true)
    expect($reply->fresh()->is_resolved)->toBeFalse();
});

it('records who resolved the thread', function () {
    [$user, $post] = $this->createUserAndPost();

    $user2 = TestUser::create(['name' => 'Jane', 'email' => 'jane@example.com']);

    $comment = $post->comment('Resolve me');
    $comment->approve();

    $comment->resolve($user2->id);

    expect($comment->fresh()->resolved_by)->toBe($user2->id);
});

it('can unresolve a thread', function () {
    [$user, $post] = $this->createUserAndPost();

    $comment = $post->comment('Toggle resolve');
    $comment->approve();

    $comment->resolve();
    expect($comment->fresh()->is_resolved)->toBeTrue();

    $comment->unresolve();
    expect($comment->fresh()->is_resolved)->toBeFalse();
    expect($comment->fresh()->resolved_by)->toBeNull();
});

it('resolved scope filters correctly', function () {
    [$user, $post] = $this->createUserAndPost();

    $c1 = $post->comment('Resolved');
    $c1->approve();
    $c1->resolve();

    $c2 = $post->comment('Unresolved');
    $c2->approve();

    expect(Comment::resolved()->count())->toBe(1);
    expect(Comment::unresolved()->count())->toBe(1);
});
