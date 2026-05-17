<?php

return [
    'base_url' => env('WB_API_BASE_URL', 'http://109.73.206.144:6969'),
    'key' => env('WB_API_KEY'),
    'limit' => (int) env('WB_API_LIMIT', 500),
    'date_from' => env('WB_API_DATE_FROM', '2020-01-01'),
    'date_to' => env('WB_API_DATE_TO'),
    'timeout' => (int) env('WB_API_TIMEOUT', 60),
];
