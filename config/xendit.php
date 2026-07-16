<?php

return [
    'api_key' => env('XENDIT_API_KEY', ''),
    'secret_key' => env('XENDIT_SECRET_KEY', ''),
    'is_production' => env('XENDIT_IS_PRODUCTION', false),
    'webhook_token' => env('XENDIT_WEBHOOK_TOKEN', ''),
];
