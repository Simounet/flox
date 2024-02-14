<?php

  return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been set up for each driver as an example of the required values.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

      'local' => [
        'driver' => 'local',
        'url' => env('APP_URL') . '/assets/poster',
        'root' => base_path('../public/assets/poster'),
      ],

      'subpage' => [
        'driver' => 'local',
        'root' => base_path('../public/assets/poster/subpage'),
      ],

      'backdrop' => [
        'driver' => 'local',
        'root' => base_path('../public/assets/backdrop'),
      ],

      'export' => [
        'driver' => 'local',
        'root' => base_path('../public/exports'),
      ],

      'languages' => [
        'driver' => 'local',
        'root' => base_path('../client/resources/languages'),
      ],

      'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'visibility' => 'public',
      ],

      's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
        'url' => env('AWS_URL'),
        'endpoint' => env('AWS_ENDPOINT'),
        'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        'throw' => false,
      ],

    ],

  ];
