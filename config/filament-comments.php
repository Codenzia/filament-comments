<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Codenzia\FilamentComments\Models\Comment;

return [

    /*
     * The comment class that should be used to store and retrieve
     * the comments.
     */
    'comment_class' => Comment::class,

    /*
     * The table name to use for the comments.
     */
    'table_name' => 'comments',

    /*
     * The table name to use for the comment reactions.
     */
    'reactions_table_name' => 'comments_reactions',

    /*
     * The table name to use for the comment channels.
     */
    'channels_table_name' => 'comment_channels',

    /*
     * The table name to use for the comment channel members.
     */
    'channel_members_table_name' => 'comment_channel_members',

    /*
     * The table name to use for tracking read status per channel/user.
     */
    'channel_reads_table_name' => 'comment_channel_reads',

    /**
     * Navigation groups
     *
     * Channels and Direct Messages each get their own collapsible sidebar group.
     * Customize the group labels here.
     */
    'navigation_groups' => [
        'channels' => 'Channels',
        'direct_messages' => 'Direct Messages',
    ],

    /**
     * Sidebar MRU (Most Recently Used) limits
     *
     * Controls the maximum number of channels and direct messages shown
     * in the sidebar navigation. Only the most recently active items
     * are displayed. Set to null to show all.
     */
    'sidebar_limit' => [
        'channels' => 5,
        'direct_messages' => 5,
    ],

    'mentionable' => [
        'model' => User::class,
        'trigger' => '@',
        'column' => [
            'id' => 'id',
            'label' => 'name', // Column name for user name/label
            'value' => 'name', // Column name for user value (used in mentions)
            'email' => 'email', // Column name for user email
            'avatar' => 'profile_photo_path', // Column or accessor for user avatar (e.g., 'avatar_url', 'profile_photo_path')
        ],
        'url' => 'admin/users/{id}', // this will be used to generate the url for the mention item
    ],

    'channel_mentionable' => [
        'trigger' => '#',
    ],

    'project_mentionable' => [
        'model' => Project::class,
        'trigger' => '$',
        'column' => [
            'id' => 'id',
            'label' => 'title',
        ],
        'url' => 'admin/projects/{id}',
    ],

    'task_mentionable' => [
        'model' => Task::class,
        'trigger' => '%',
        'column' => [
            'id' => 'id',
            'label' => 'title',
        ],
        'url' => 'admin/tasks/{id}',
    ],

    /*
     * The user model that should be used when associating comments with
     * commentators. If null, the default user provider from your
     * Laravel authentication configuration will be used.
     */
    'user_model' => null,

    /*
     * The project model that should be used for channel association.
     */
    'project_model' => Project::class,

    /*
     * The Event model that should be used when persisting event-type comments
     * into a dedicated events table. You can change this to point to your own
     * model class, e.g. \App\Models\Event::class.
     *
     * If null, no Event model records will be created – events will only be
     * stored as JSON on the comment itself.
     */
    'event_model' => null,

    /*
     * Column mapping for the configured event_model. Keys are the logical fields
     * extracted from the event comment payload; values are the actual column
     * names on your Event model's table.
     *
     * You can customize these to match your Event schema.
     */
    'event_model_columns' => [
        'title' => 'title',
        'date' => 'date',
        'description' => 'description',
        'comment_id' => 'comment_id',
        'user_id' => 'user_id',
    ],

    /**
     * Auto-approve new comments immediately.
     * Set to false to require manual moderation before comments are visible.
     */
    'auto_approve' => true,

    /**
     * Determines if replies will be deleted when comments are deleted
     */
    'delete_replies_along_comments' => false,

    'editor' => [
        'placeholder' => 'Type your comment here...',
        'height' => 100,
    ],

    /**
     * Composer appearance
     *
     * Customize the background color of the comment composer.
     * Accepts any valid CSS color value (hex, rgb, hsl, etc.).
     */
    'composer' => [
        'bg' => '#ffffff',
        'dark_bg' => '#16181C',
        'show_settings' => false,
    ],

    /**
     * Available reaction types with their emoji icons
     * You can customize the reaction types and emojis here
     */
    'reactions' => [
        'like' => '👍',
        'love' => '❤️',
        'laugh' => '😄',
        'wow' => '😮',
        'sad' => '😢',
        'angry' => '😠',
    ],

    /**
     * Permissions configuration
     *
     * Works seamlessly with Filament Shield / Spatie Permission.
     * Define permission names that map to your Spatie permissions.
     * Shield auto-generates these when you run `shield:generate`.
     *
     * Set a permission to `null` to allow any authenticated user.
     * Set to a string to require that specific Spatie permission.
     */
    'permissions' => [
        'create_channel' => 'create_comment_channel',
        'update_channel' => 'update_comment_channel',
        'delete_channel' => 'delete_comment_channel',
        'view_channel' => 'view_comment_channel',
        'view_direct_message' => null, // null = any authenticated user can view DMs
        'create_direct_message' => null, // null = any authenticated user can start DMs
        'delete_direct_message' => null, // null = any authenticated user can leave DMs
        'add_member_direct_message' => null, // null = any authenticated user can add members to DMs

        // Gates every comment-write surface: posting, replying, reactions,
        // joining channels, casting votes, responding to events/meetings,
        // ticking todo items, answering surveys, acknowledging risks, pinning,
        // and watching. Default null = any authenticated user (back-compat).
        // Set to a Spatie permission name (e.g. 'create_comment') for public
        // demos or read-only deployments where commenting must be locked down.
        'create_comment' => null,
    ],

    /**
     * Show "Add to Calendar" on event comments. When true, the button is only
     */
    'enable_add_to_calendar' => true,

    /*
     * The table name to use for the comment bookmarks.
     */
    'bookmarks_table_name' => 'comment_bookmarks',

    /*
     * The table name to use for the comment watches.
     */
    'watches_table_name' => 'comment_watches',

    /**
     * Link Previews (Open Graph cards)
     *
     * Automatically fetch Open Graph metadata for URLs in comments
     * and display rich preview cards.
     */
    'link_previews' => [
        'enabled' => true,
        'cache_ttl' => 3600,
    ],

    /**
     * Code Syntax Highlighting
     *
     * Enable syntax highlighting for code blocks in comments.
     * Uses highlight.js for display-side rendering.
     */
    'code_highlighting' => true,

    /**
     * Email Digest
     *
     * Send a daily email digest of unread comments to users.
     * Register the schedule: `php artisan filament-comments:send-digest`
     */
    'digest' => [
        'enabled' => false,
        'schedule' => 'daily',
        'time' => '09:00',
    ],

];
