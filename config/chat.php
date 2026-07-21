<?php

return [
    'enabled' => env('CHAT_MODULE_ENABLED', true),

    'uploads' => [
        'disk' => env('CHAT_UPLOAD_DISK', 'public'),
        'max_size_kb' => (int) env('CHAT_UPLOAD_MAX_SIZE_KB', 10240),
        'allowed_mimes' => [
            'jpg', 'jpeg', 'png', 'gif', 'webp',
            'mp4', 'mov', 'webm',
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt',
            'mp3', 'wav', 'm4a', 'ogg',
        ],
    ],

    'retention' => [
        'soft_delete_messages' => true,
        'audit_every_mutation' => true,
    ],

    'realtime' => [
        'driver' => env('CHAT_REALTIME_DRIVER', 'rest-polling'),
        'poll_interval_ms' => (int) env('CHAT_POLL_INTERVAL_MS', 5000),
    ],

    'rate_limits' => [
        'requests_per_minute' => (int) env('CHAT_REQUESTS_PER_MINUTE', 1200),
        'messages_per_minute' => (int) env('CHAT_MESSAGES_PER_MINUTE', 60),
    ],
];
