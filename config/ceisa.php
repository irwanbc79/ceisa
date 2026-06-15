<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CEISA H2H (Host-to-Host) Bea Cukai
    |--------------------------------------------------------------------------
    |
    | Konfigurasi integrasi dengan CEISA 4.0 Bea Cukai Indonesia.
    | Kredensial per-user (username, password, api_key) disimpan terenkripsi di
    | tabel ceisa_credentials. Nilai di sini adalah fallback/default level aplikasi.
    |
    | Host gateway resmi (lihat openapi.beacukai.go.id). Auth ada di /v1/openapi-auth,
    | layanan Pabean (kirim dokumen) di /v2/openapi.
    */
    'base_url' => rtrim(env('CEISA_BASE_URL', 'https://apis-gw.beacukai.go.id'), '/'),

    /*
    | Header API Key wajib pada SEMUA request (auth maupun layanan): beacukai-api-key.
    | Nilai key disimpan per-user terenkripsi di kolom api_key tabel ceisa_credentials.
    */
    'api_key_header' => env('CEISA_API_KEY_HEADER', 'beacukai-api-key'),

    'app_id' => env('CEISA_APP_ID'),

    'api_key' => env('CEISA_API_KEY'),

    'webhook_url' => env('CEISA_WEBHOOK_URL', 'https://ceisa.m2b.co.id/api/webhook/ceisa'),

    'webhook_secret' => env('CEISA_WEBHOOK_SECRET'),

    /*
    | Endpoint relatif terhadap base_url (host gateway).
    | - token : login H2H (POST username+password, header beacukai-api-key) -> access_token.
    |   Terverifikasi dari dokumentasi resmi: {host}/v1/openapi-auth/user/login.
    | - submit/status : layanan Pabean di /v2/openapi (46 resource). Path resource
    |   spesifik mengikuti Swagger JSON openapi (Pabean) — override via .env bila berbeda.
    */
    'endpoints' => [
        'token' => env('CEISA_TOKEN_ENDPOINT', '/v1/openapi-auth/user/login'),
        'submit' => env('CEISA_SUBMIT_ENDPOINT', '/v2/openapi/document'),
        'status' => env('CEISA_STATUS_ENDPOINT', '/v2/openapi/document/status'),
    ],

    /*
    | Margin (detik) sebelum token benar-benar expired agar di-refresh lebih awal.
    */
    'token_refresh_margin' => env('CEISA_TOKEN_REFRESH_MARGIN', 60),

    /*
    | Umur token fallback (detik) bila response login tak menyertakan expires_in.
    | Access Token CEISA 4.0 berumur ~5 menit.
    */
    'token_ttl_fallback' => env('CEISA_TOKEN_TTL_FALLBACK', 300),

    'timeout' => env('CEISA_HTTP_TIMEOUT', 30),

    /*
    | Tipe dokumen yang didukung. Kode mengikuti jenis dokumen Bea Cukai.
    | MVP fokus ke dokumen kepabeanan impor, ekspor, TPB, dan Rush Handling.
    |*/
    'doc_types' => [
        'BC20' => 'BC 2.0 — Pemberitahuan Impor Barang',
        'BC24' => 'BC 2.4 — Impor Barang Ditimbun di TPB',
        'TPB' => 'Portal TPB (BC 2.3, 2.5, 2.7, 4.0)',
        'BC30' => 'BC 3.0 — Pemberitahuan Ekspor Barang (PEB)',
        'RUSH' => 'Pengajuan Rush Handling',
    ],

    /*
    | Error Response Code resmi CEISA 4.0 (openapi.beacukai.go.id/portal/pages/error response code).
    | Ini kode VALIDASI payload JSON, bukan error auth.
    */
    'error_codes' => [
        '1008' => 'Nilai isian harus berasal dari salah satu pilihan (enumeration di JSON Schema).',
        '1023' => 'Nilai isian tidak sesuai pola/pattern (perhatikan karakter & jumlah karakter).',
        '1028' => 'Elemen mandatory wajib ada pada data JSON yang dikirim.',
        '1042' => 'Nilai isian harus sama dengan CONSTANT yang ditetapkan sesuai urutannya.',
    ],
];
