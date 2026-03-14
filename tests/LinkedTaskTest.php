<?php

use Codenzia\FilamentComments\Tests\Fixtures\CreatesTestSchema;

uses(CreatesTestSchema::class);

it('can link a comment to a task', function () {
    [$user, $post] = $this->createUserAndPost();

    $comment = $post->comment('Linked to task');
    $comment->approve();

    $comment->update(['linked_task_id' => 42]);

    expect($comment->fresh()->linked_task_id)->toBe(42);
});

it('handles null linked_task_id gracefully', function () {
    [$user, $post] = $this->createUserAndPost();

    $comment = $post->comment('No task link');
    $comment->approve();

    expect($comment->linked_task_id)->toBeNull();
});

it('linked_task_id is fillable', function () {
    [$user, $post] = $this->createUserAndPost();

    $comment = $post->comments()->create([
        'comment' => 'Created with link',
        'user_id' => $user->id,
        'is_approved' => true,
        'linked_task_id' => 99,
    ]);

    expect($comment->linked_task_id)->toBe(99);
});
