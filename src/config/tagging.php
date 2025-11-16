<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Table Prefix
    |--------------------------------------------------------------------------
    |
    | Prefix for package tables to avoid conflicts with existing tables.
    | Set to empty string '' if you don't want a prefix.
    |
    */

    'table_prefix' => env('TAGGING_TABLE_PREFIX', 'tagging_'),

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    |
    | Specify the table names used by the package. These will be prefixed
    | with the table_prefix value above.
    |
    */

    'tables' => [
        'tags' => env('TAGGING_TAGS_TABLE', 'tags'),
        'tag_configs' => env('TAGGING_TAG_CONFIGS_TABLE', 'tag_configs'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Prefix
    |--------------------------------------------------------------------------
    |
    | The prefix to use when no tag configuration is found for a model.
    | This is used in the generateFallbackTag() method.
    |
    */

    'fallback_prefix' => env('TAGGING_FALLBACK_PREFIX', 'TAG'),

    /*
    |--------------------------------------------------------------------------
    | Default Tag Configuration
    |--------------------------------------------------------------------------
    |
    | Default values for tag configuration when creating new configurations.
    |
    */

    'defaults' => [
        'separator' => '-',
        'number_format' => 'sequential', // sequential, branch_based, or random
        'auto_generate' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    |
    | Enable or disable the package's API routes for managing tag configurations.
    | You can also customize the route prefix and middleware.
    |
    */

    'routes' => [
        'enabled' => env('TAGGING_ROUTES_ENABLED', true),
        'prefix' => env('TAGGING_ROUTE_PREFIX', 'api/tag-configs'),
        'tags_prefix' => env('TAGGING_TAGS_ROUTE_PREFIX', 'api/tags'),
        'middleware' => ['api'], // Add 'auth:sanctum' or other middleware as needed
    ],

];