<?php

return [
    // Admin panel URL prefix. Change ADMIN_PATH in .env to something non-obvious in production.
    'admin_path' => env('ADMIN_PATH', 'admin'),

    // Hours a preview link stays valid.
    'preview_hours' => (int) env('PREVIEW_LINK_HOURS', 72),

    // Max upload size in KB for the media library.
    'max_upload_kb' => (int) env('MAX_UPLOAD_KB', 8192),

    'google_maps_key' => env('GOOGLE_MAPS_API_KEY'),

    // 301 /page → /page/ (canonical URLs end with a slash). The test HTTP client strips trailing
    // slashes, so the test suite turns this off and enables it explicitly where it is tested.
    'force_trailing_slash' => (bool) env('FORCE_TRAILING_SLASH', true),
];
