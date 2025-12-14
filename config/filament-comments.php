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
    'table_name' => 'filament_comments',

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
];
