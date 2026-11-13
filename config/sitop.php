<?php

return [
    'default_timezone' => env('SITOP_DEFAULT_TIMEZONE', 'Asia/Jakarta'),

    'identifiers' => [
        'domain_primary_key' => 'ulid',
    ],

    'attachments' => [
        'disk' => env('SITOP_ATTACHMENTS_DISK', 'attachments'),
        'max_size_kilobytes' => (int) env('SITOP_ATTACHMENT_MAX_SIZE_KB', 10240),
        'allowed_extensions' => [
            'csv',
            'doc',
            'docx',
            'jpeg',
            'jpg',
            'pdf',
            'png',
            'txt',
            'webp',
            'xls',
            'xlsx',
        ],
        'allowed_mime_types' => [
            'application/msword',
            'application/pdf',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'image/jpeg',
            'image/png',
            'image/webp',
            'text/csv',
            'text/plain',
        ],
    ],
];
