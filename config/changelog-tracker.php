<?php

return [

    'github' => [
        'token' => env('GITHUB_TOKEN'),
        'api_base' => 'https://api.github.com',
        'user_agent' => 'ChangelogTracker/1.0',
    ],

    'defaults' => [
        'check_interval_minutes' => 30,
        'include_prereleases' => false,
    ],

    'notifications' => [
        'max_attempts' => 3,
        'retry_window_hours' => 24,
    ],

];
