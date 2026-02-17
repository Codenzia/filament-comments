<?php

return [

    /*
     * The comment class that should be used to store and retrieve
     * the comments.
     */
    'comment_class' => \Codenzia\FilamentComments\Models\Comment::class,

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

    /**
     * The navigation group name for the Filament resource.
     */
    'navigation_group' => 'Channels',

    'mentionable' => [
        'model' => \App\Models\User::class,
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
        'model' => \App\Models\Project::class,
        'trigger' => '$',
        'column' => [
            'id' => 'id',
            'label' => 'title',
        ],
        'url' => 'admin/projects/{id}',
    ],

    'task_mentionable' => [
        'model' => \App\Models\Task::class,
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
    'project_model' => \App\Models\Project::class,

    /**
     * Determines if replies will be deleted when comments are deleted
     */
    'delete_replies_along_comments' => false,

    'editor' => [
        'placeholder' => 'Type your comment here...',
        'height' => 100,
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
     */
    'permissions' => [
        /**
         * Roles allowed to create and manage channels.
         * If empty, any authenticated user can create channels.
         */
        'create_channels_roles' => [
            'super_admin',
        ],
    ],

    'enable_add_to_calendar' => true,
];
