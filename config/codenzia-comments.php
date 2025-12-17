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
    'mentionable' => [
        'model' => \App\Models\User::class,
        'column' => [
            'id' => 'id',
            'label' => 'name',
            'value' => 'name',
            'avatar' => 'name',
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
        'height' => 200,
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
     * Mentions configuration
     */
    'mentions' => [
        'enabled' => true,
        'min_chars' => 1,
        'max_results' => 10,
    ],
    'default' => [
        'trigger_with' => [
            '@',
            '#',
            '%',
        ],
        'trigger_configs' => [
            '#' => [
                'lookupKey' => 'value',
                'prefix' => '[',
                'suffix' => ']',
                'labelKey' => 'label',
                'hintKey' => null,
            ],
            '%' => [
                'lookupKey' => 'value',
                'prefix' => '%',
                'suffix' => '%',
                'labelKey' => 'id',
                'hintKey' => null,
            ],
        ],
        'lookup_key' => 'value',
        'menu_show_min_length' => 2,
        'menu_item_limit' => 10,
        'prefix' => '',
        'suffix' => '',
        'label_key' => 'label',
        'hint_key' => null,
    ],    
];
