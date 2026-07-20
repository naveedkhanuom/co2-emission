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
        'token' => env('POSTMARK_TOKEN'),
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

    /*
    |--------------------------------------------------------------------------
    | Anthropic (Claude) — primary LLM provider
    |--------------------------------------------------------------------------
    | Used by App\Services\AI\ClaudeService. Never read ANTHROPIC_API_KEY
    | directly in controllers/services — always go through this config.
    */
    'anthropic' => [
        'key'         => env('ANTHROPIC_API_KEY'),
        'model'       => env('ANTHROPIC_MODEL', 'claude-sonnet-4-6'),
        'version'     => env('ANTHROPIC_VERSION', '2023-06-01'),
        'base_url'    => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
        'max_tokens'  => (int) env('ANTHROPIC_MAX_TOKENS', 1024),
        'timeout'     => (int) env('ANTHROPIC_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | OCR (bill extraction)
    |--------------------------------------------------------------------------
    | Read via config() — never env() in controllers, which returns null under
    | `php artisan config:cache` in production.
    */
    'ocr_space' => [
        'key' => env('OCR_SPACE_API_KEY'),
    ],

    'tesseract' => [
        'path' => env('TESSERACT_PATH', 'tesseract'),
    ],

];
