<?php

return [
    // wa_link (default) | log | sms | whatsapp_api (dua terakhir: antre job, nonaktif, tanpa klien nyata)
    'notifier' => env('JANJI_NOTIFIER', 'wa_link'),
    'token_ttl_days' => (int) env('JANJI_TOKEN_TTL_DAYS', 7),
    'rate_per_minute' => (int) env('JANJI_RATE_PER_MINUTE', 10),
    'channels' => [
        'sms' => ['enabled' => (bool) env('JANJI_SMS_ENABLED', false)],
        'whatsapp_api' => ['enabled' => (bool) env('JANJI_WHATSAPP_API_ENABLED', false)],
    ],
    'reminder_days_before' => (int) env('JANJI_REMINDER_DAYS', 2),
    // manual (default) | llm (butuh ANTHROPIC_API_KEY; bila kosong jatuh ke manual)
    'drafter' => env('JANJI_DRAFTER', 'manual'),
    'llm' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('JANJI_LLM_MODEL', 'claude-sonnet-5-5'),
        'url' => 'https://api.anthropic.com/v1/messages',
    ],
    'money_max' => 99999999999,
];
