<?php

use Codenzia\FilamentComments\Tests\Fixtures\CreatesTestSchema;
use Codenzia\FilamentComments\Tests\Fixtures\TestPost;
use Codenzia\FilamentComments\Tests\Fixtures\TestUser;

uses(CreatesTestSchema::class);

it('can watch a commentable model', function () {
    [$user, $post] = $this->createUserAndPost();

    $post->toggleWatch($user->id);

    expect($post->isWatchedBy($user->id))->toBeTrue();
});

it('can unwatch a commentable model', function () {
    [$user, $post] = $this->createUserAndPost();

    $post->toggleWatch($user->id); // watch
    expect($post->isWatchedBy($user->id))->toBeTrue();

    $post->toggleWatch($user->id); // unwatch
    expect($post->isWatchedBy($user->id))->toBeFalse();
});

it('toggleWatch toggles correctly', function () {
    [$user, $post] = $this->createUserAndPost();

    $result1 = $post->toggleWatch($user->id);
    expect($result1)->toBeTrue(); // now watching

    $result2 = $post->toggleWatch($user->id);
    expect($result2)->toBeFalse(); // now unwatched

    $result3 = $post->toggleWatch($user->id);
    expect($result3)->toBeTrue(); // watching again
});

it('isWatchedBy returns correct boolean', function () {
    [$user, $post] = $this->createUserAndPost();

    expect($post->isWatchedBy($user->id))->toBeFalse();

    $post->toggleWatch($user->id);

    expect($post->isWatchedBy($user->id))->toBeTrue();
});

it('watches are polymorphic', function () {
    [$user, $post] = $this->createUserAndPost();

    $post2 = TestPost::create(['title' => 'Another Post']);

    $post->toggleWatch($user->id);
    $post2->toggleWatch($user->id);

    expect($post->isWatchedBy($user->id))->toBeTrue();
    expect($post2->isWatchedBy($user->id))->toBeTrue();

    // Unwatch one, other stays
    $post->toggleWatch($user->id);

    expect($post->isWatchedBy($user->id))->toBeFalse();
    expect($post2->isWatchedBy($user->id))->toBeTrue();
});

it('multiple users can watch the same model', function () {
    [$user, $post] = $this->createUserAndPost();

    $user2 = TestUser::create(['name' => 'Jane', 'email' => 'jane@example.com']);

    $post->toggleWatch($user->id);
    $post->toggleWatch($user2->id);

    expect($post->isWatchedBy($user->id))->toBeTrue();
    expect($post->isWatchedBy($user2->id))->toBeTrue();
    expect($post->commentWatchers()->count())->toBe(2);
});
