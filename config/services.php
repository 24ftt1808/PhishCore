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

     'google_safe_browsing' => [
        'key' => env('GOOGLE_SAFE_BROWSING_API_KEY'),
    ],

    // Optional extra check: a language model reads the message text. Off unless AI_TEXT_CHECK=true.
    // PRIVACY: when on, message text is sent to Google. Free-tier data may be used by Google.
    'ai_text' => [
        'enabled' => env('AI_TEXT_CHECK', false),
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.5-flash-lite'),
    ],

    // Floating Safety Adviser chat (Groq). Off unless CHATBOT_ENABLED=true, a key and a model are set.
    // PRIVACY: when on, what users type in the chat is sent to Groq.
    'chat' => [
        'enabled' => env('CHATBOT_ENABLED', false),
        'key' => env('GROQ_API_KEY'),
        'model' => env('GROQ_MODEL'),
        'url' => env('GROQ_URL', 'https://api.groq.com/openai/v1/chat/completions'),
    ],

    'ocr_space' => [
    'key' => env('OCR_SPACE_API_KEY'),
],

       'virustotal' => [
        'key' => env('VIRUSTOTAL_API_KEY'),
    ],

        'abstractapi_phone' => [
        'key' => env('ABSTRACTAPI_PHONE_KEY'),
    ],

];