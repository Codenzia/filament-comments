<?php

use Codenzia\FilamentComments\Tests\Fixtures\TestPost;
use Codenzia\FilamentComments\Tests\Fixtures\TestUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;

it('can render comments component', function () {
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
    $post->comments->first()->approve();

    $view = Blade::render('<x-codenzia-comments::comments :model="$post" />', ['post' => $post]);

    expect($view)->toContain('This is a test comment')
        ->toContain('John');
});
