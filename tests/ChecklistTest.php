<?php

use Codenzia\FilamentComments\Tests\Fixtures\CreatesTestSchema;

uses(CreatesTestSchema::class);

it('can toggle a checklist item in comment body', function () {
    [$user, $post] = $this->createUserAndPost();

    $comment = $post->comment('<p>[ ] Task A</p><p>[x] Task B</p><p>[ ] Task C</p>');
    $comment->approve();

    // Toggle the first unchecked item (index 0)
    $body = $comment->comment;
    $counter = 0;
    $body = preg_replace_callback(
        '/\[([ xX])\]/',
        function ($matches) use (&$counter) {
            if ($counter++ === 0) {
                return $matches[1] === ' ' ? '[x]' : '[ ]';
            }

            return $matches[0];
        },
        $body
    );

    $comment->update(['comment' => $body]);

    expect($comment->fresh()->comment)->toContain('[x] Task A');
});

it('updates [ ] to [x] correctly', function () {
    [$user, $post] = $this->createUserAndPost();

    $comment = $post->comment('<p>[ ] Unchecked item</p>');
    $comment->approve();

    $body = str_replace('[ ]', '[x]', $comment->comment);
    $comment->update(['comment' => $body]);

    expect($comment->fresh()->comment)->toContain('[x] Unchecked item');
    expect($comment->fresh()->comment)->not->toContain('[ ] Unchecked item');
});

it('updates [x] to [ ] correctly', function () {
    [$user, $post] = $this->createUserAndPost();

    $comment = $post->comment('<p>[x] Checked item</p>');
    $comment->approve();

    $body = str_replace('[x]', '[ ]', $comment->comment);
    $comment->update(['comment' => $body]);

    expect($comment->fresh()->comment)->toContain('[ ] Checked item');
    expect($comment->fresh()->comment)->not->toContain('[x] Checked item');
});

it('handles multiple checklist items independently', function () {
    [$user, $post] = $this->createUserAndPost();

    $comment = $post->comment('<p>[ ] Item 1</p><p>[ ] Item 2</p><p>[ ] Item 3</p>');
    $comment->approve();

    // Toggle only the second item (index 1)
    $body = $comment->comment;
    $counter = 0;
    $body = preg_replace_callback(
        '/\[([ xX])\]/',
        function ($matches) use (&$counter) {
            if ($counter++ === 1) {
                return '[x]';
            }

            return $matches[0];
        },
        $body
    );

    $comment->update(['comment' => $body]);

    $fresh = $comment->fresh()->comment;
    expect($fresh)->toContain('[ ] Item 1');
    expect($fresh)->toContain('[x] Item 2');
    expect($fresh)->toContain('[ ] Item 3');
});
