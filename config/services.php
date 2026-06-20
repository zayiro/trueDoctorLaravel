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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'zoom' => [
        'account_id'           => env('ZOOM_ACCOUNT_ID'),
        'client_id'            => env('ZOOM_CLIENT_ID'),
        'client_secret'        => env('ZOOM_CLIENT_SECRET'),
        'host_email'           => env('ZOOM_HOST_EMAIL'),
        'webhook_secret_token' => env('ZOOM_WEBHOOK_SECRET_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Credenciales de Inteligencia Artificial (openDoctor)
    |--------------------------------------------------------------------------
    */
    
    'ai_vision' => [
        // Proveedor por defecto cuando no se especifica explícitamente.
        // Cambialo aquí (o vía .env) para "switchear" sin tocar código.
        // Switch global instantáneo: si OpenAI tiene una caída, cambias AI_VISION_PROVIDER=claude en el .env del EC2 y reinicias el queue worker (php artisan queue:restart) — cero cambios de código.
        'default' => env('AI_VISION_PROVIDER', 'openai'), // 'openai' | 'claude'
    ],
    
    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'vision_model' => env('OPENAI_VISION_MODEL', 'gpt-5.4'),
    ],
    
    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'vision_model' => env('ANTHROPIC_VISION_MODEL', 'claude-sonnet-4-6'),
    ],

];
