<?php

return [
    // wa_link (default) | log
    'notifier' => env('JANJI_NOTIFIER', 'wa_link'),
    'token_ttl_days' => (int) env('JANJI_TOKEN_TTL_DAYS', 7),
    'rate_per_minute' => (int) env('JANJI_RATE_PER_MINUTE', 10),
    'money_max' => 99999999999,
];
