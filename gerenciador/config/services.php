<?php

//by Nicolas Duran

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

    // Credenciais Postmark.
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    // Credenciais Resend.
    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    // Credenciais AWS SES.
    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Config para notificacoes Slack.
    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Endpoint WebSocket Java.
    'java_ws' => [
        'url' => env('JAVA_WS_URL', ''),
    ],

    // Bridge HTTP para IA em Java.
    'java_ai_bridge' => [
        'url' => env('JAVA_AI_HTTP_URL', 'http://127.0.0.1:3100/analises'),
    ],

];
