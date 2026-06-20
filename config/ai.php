<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Hybrid AI (Validasi Cerdas Dokumen CEISA)
    |--------------------------------------------------------------------------
    |
    | Validator AI hybrid multi-provider (Gemini, DeepSeek) — sama pola
    | dengan project lain Bismillah Digital. HybridAiClient mencoba provider
    | sesuai urutan `order`; yang pertama terkonfigurasi & sukses dipakai
    | (failover). Lapisan aturan deterministik tetap jalan walau semua AI mati.
    */

    // Aktif/nonaktif fitur validasi AI secara global.
    'enabled' => (bool) env('AI_VALIDATION_ENABLED', true),

    // Urutan failover provider. Provider tanpa API key otomatis dilewati.
    'order' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('AI_ORDER', 'gemini,deepseek')),
    ))),

    // Timeout (detik) tiap panggilan provider.
    'timeout' => (int) env('AI_TIMEOUT', 45),

    // Batas token output per panggilan.
    'max_tokens' => (int) env('AI_MAX_TOKENS', 1500),

    'providers' => [

        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'base_url' => rtrim(env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'), '/'),
            'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
        ],

        'deepseek' => [
            'api_key' => env('DEEPSEEK_API_KEY'),
            'base_url' => rtrim(env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'), '/'),
            'model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
        ],

    ],
];
