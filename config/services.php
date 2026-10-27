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

    // Optional "are you human" check for guest scans (Cloudflare Turnstile, free). Off until both keys are set.
    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
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

    // Optional "Show page preview" button on link scans (urlscan.io). Off unless PAGE_PREVIEW=true and a key is set.
    // PRIVACY: when on, the link is sent to urlscan.io as an UNLISTED scan (not shown in its public search) when
    // someone clicks the button. Pictures are deleted after keep_days (run previews:prune, scheduled daily).
    // Free plan per day: 1,000 unlisted scans, 50 private scans.
    'page_preview' => [
        'enabled' => env('PAGE_PREVIEW', false),
        'key' => env('URLSCAN_API_KEY'),
        'visibility' => env('URLSCAN_VISIBILITY', 'unlisted'),
        'daily_cap' => (int) env('PAGE_PREVIEW_DAILY_CAP', 200),
        'keep_days' => (int) env('PAGE_PREVIEW_KEEP_DAYS', 7),
        // Also read the page as it looked once loaded in the sandbox and show a short note under the picture.
        // Display only: never changes the score. Uses no extra scan allowance, only a download of the loaded page.
        'read_page' => (bool) env('PAGE_PREVIEW_READ_PAGE', true),
    ],

    // Run a link scan's independent network checks at the same time (separate PHP processes) instead of one by one.
    // Faster, but each check starts its own PHP process, so it uses more memory for a moment. Off unless SCAN_PARALLEL=true.
    'scan' => [
        'parallel' => (bool) env('SCAN_PARALLEL', false),
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