<?php

use Codenzia\FilamentComments\Services\LinkPreviewService;
use Codenzia\FilamentComments\Tests\Fixtures\CreatesTestSchema;

uses(CreatesTestSchema::class);

it('stores link previews as JSON', function () {
    [$user, $post] = $this->createUserAndPost();

    $comment = $post->comment('Check this out');
    $comment->approve();

    $previews = [
        ['url' => 'https://example.com', 'title' => 'Example', 'description' => 'An example site', 'domain' => 'example.com', 'image' => null],
    ];

    $comment->update(['link_previews' => $previews]);

    $fresh = $comment->fresh();
    expect($fresh->link_previews)->toBeArray();
    expect($fresh->link_previews)->toHaveCount(1);
    expect($fresh->link_previews[0]['title'])->toBe('Example');
});

it('handles null link_previews gracefully', function () {
    [$user, $post] = $this->createUserAndPost();

    $comment = $post->comment('No links here');
    $comment->approve();

    expect($comment->link_previews)->toBeNull();
});

it('extracts URLs from comment HTML', function () {
    $service = new LinkPreviewService;

    $html = '<p>Check out <a href="https://example.com">this link</a> and https://another.com/page</p>';

    $urls = $service->extractUrls($html);

    expect($urls)->toContain('https://example.com');
    expect($urls)->toContain('https://another.com/page');
});

it('excludes image URLs from extraction', function () {
    $service = new LinkPreviewService;

    $html = '<p>See <img src="https://example.com/photo.jpg"> and https://real-link.com</p>';

    $urls = $service->extractUrls($html);

    expect($urls)->toContain('https://real-link.com');
    expect($urls)->not->toContain('https://example.com/photo.jpg');
});

it('returns empty array when no URLs found', function () {
    $service = new LinkPreviewService;

    $urls = $service->extractUrls('<p>No links here, just text.</p>');

    expect($urls)->toBeEmpty();
});
