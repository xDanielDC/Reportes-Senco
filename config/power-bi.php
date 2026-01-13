<?php

return [
    'user_id' => env('POWERBI_USER_ID'),
    'grant_type' => env('POWERBI_GRANT_TYPE', 'password'),
    'client_secret' => env('POWERBI_CLIENT_SECRET'),
    'client_id' => env('POWERBI_CLIENT_ID'),
    'resource' => env('POWERBI_RESOURCE', 'https://analysis.windows.net/powerbi/api'),
    'username' => env('POWERBI_USERNAME'),
    'password' => env('POWERBI_PASSWORD')
];
