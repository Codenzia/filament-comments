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

    /**
     * The navigation group name for the Filament resource.
     */
    'navigation_group' => 'Channels',

    'mentionable' => [
        'model' => \App\Models\User::class,
        'column' => [
            'id' => 'id',
            'label' => 'name', // Column name for user name/label
            'value' => 'name', // Column name for user value (used in mentions)
            'email' => 'email', // Column name for user email
            'avatar' => 'profile_photo_path', // Column or accessor for user avatar (e.g., 'avatar_url', 'profile_photo_path')
        ],
        'url' => 'admin/users/{id}', // this will be used to generate the url for the mention item
    ],
    /*
     * The user model that should be used when associating comments with
     * commentators. If null, the default user provider from your
     * Laravel authentication configuration will be used.
     */
    'user_model' => null,

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
];
