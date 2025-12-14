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

    $user = TestUser::create(['name' => 'John', 'email' => 'john@example.com']);
    $post = TestPost::create(['title' => 'My Post']);

    $this->actingAs($user);

    $post->comment('This is a test comment');

    expect($post->comments)->toHaveCount(1);
    expect($post->comments->first()->comment)->toBe('This is a test comment');
    expect($post->comments->first()->user_id)->toBe($user->id);
});
