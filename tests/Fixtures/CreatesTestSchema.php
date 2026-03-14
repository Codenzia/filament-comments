<?php

namespace Codenzia\FilamentComments\Tests\Fixtures;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait CreatesTestSchema
{
    protected function createTestSchema(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('posts')) {
            Schema::create('posts', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('comment_channels')) {
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
        }

        if (! Schema::hasTable('comments')) {
            Schema::create('comments', function (Blueprint $table) {
                $table->id();
                $table->nullableMorphs('commentable');
                $table->text('comment');
                $table->string('type')->nullable();
                $table->boolean('is_approved')->default(false);
                $table->boolean('is_pinned')->default(false);
                $table->boolean('is_resolved')->default(false);
                $table->unsignedBigInteger('resolved_by')->nullable();
                $table->unsignedBigInteger('linked_task_id')->nullable();
                $table->json('link_previews')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('channel_id')->nullable();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->foreign('parent_id')->references('id')->on('comments')->onDelete('cascade');
                $table->foreign('channel_id')->references('id')->on('comment_channels')->onDelete('set null');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('comment_bookmarks')) {
            Schema::create('comment_bookmarks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('comment_id');
                $table->timestamps();
                $table->unique(['user_id', 'comment_id']);
            });
        }

        if (! Schema::hasTable('comment_watches')) {
            Schema::create('comment_watches', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->nullableMorphs('watchable');
                $table->timestamps();
                $table->unique(['user_id', 'watchable_type', 'watchable_id']);
            });
        }

        if (! Schema::hasTable('comments_reactions')) {
            Schema::create('comments_reactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('comment_id');
                $table->unsignedBigInteger('user_id');
                $table->string('reaction_type');
                $table->timestamps();
            });
        }
    }

    protected function createUserAndPost(): array
    {
        $this->createTestSchema();

        $user = TestUser::create(['name' => 'John', 'email' => 'john@example.com']);
        $post = TestPost::create(['title' => 'My Post']);

        $this->actingAs($user);

        return [$user, $post];
    }
}
