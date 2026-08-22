<?php

return [
    'url' => env('COSMIAN_KMS_URL', 'http://localhost:9998'),
    'api_key' => env('COSMIAN_KMS_API_KEY', ''),
    'timeout' => (int) env('COSMIAN_KMS_TIMEOUT', 30),
    'retry_times' => (int) env('COSMIAN_KMS_RETRY_TIMES', 3),
    'verify_ssl' => (bool) env('COSMIAN_KMS_VERIFY_SSL', true),
];