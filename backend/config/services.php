<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => App\Models\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],

    'tmdb' => [
      'key' => env('TMDB_API_KEY'),
      'poster' => 'https://image.tmdb.org/t/p/w185',
      'poster_subpage' => 'https://image.tmdb.org/t/p/w342',
      'backdrop' => 'https://image.tmdb.org/t/p/w1280',
    ],

    'imdb' => [
      'url' => 'https://www.imdb.com/title/',
    ],

    'fp' => [
      'host' => env('FP_HOST'),
      'port' => env('FP_PORT'),
    ],

];
