<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Federation
    |--------------------------------------------------------------------------
    |
    | This section is about the server federation through Activity Pub.
    */

    'federation' => [
         // Should be disabled for solo mode only
        'enabled' => env('FEDERATION_ENABLED', true),
    ]
];
