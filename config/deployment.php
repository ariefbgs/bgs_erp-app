<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Root
    |--------------------------------------------------------------------------
    |
    | Canonical root of the installed Laravel application.
    |
    */

    'application_root' => base_path(),

    /*
    |--------------------------------------------------------------------------
    | Private Deployment Root
    |--------------------------------------------------------------------------
    |
    | Deployment artifacts are private runtime artifacts and must not use
    | storage/app/public or the application's default filesystem disk.
    |
    */

    'private_root' => storage_path('app/deployment'),

    'paths' => [

        'packages' => storage_path('app/deployment/packages'),

        'staging' => storage_path('app/deployment/staging'),

        'recovery' => storage_path('app/deployment/recovery'),

    ],

];