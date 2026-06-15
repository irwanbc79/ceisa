<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CEISA H2H (Host-to-Host) Bea Cukai
    |--------------------------------------------------------------------------
    |
    | Konfigurasi integrasi dengan CEISA 4.0 Bea Cukai Indonesia.
    | Kredensial per-user (app_id, api_key) disimpan terenkripsi di tabel
    | ceisa_credentials. Nilai di sini adalah fallback/default level aplikasi.
    |
    */

    'base_url' => rtrim(env('CEISA_BASE_URL', 'https://apis-gw.beacukai.go.id'), '/'),

    'app_id' => env('CEISA_APP_ID'),

    'api_key' => env('CEISA_API_KEY'),

    'webhook_url' => env('CEISA_WEBHOOK_URL', 'https://ceisa.m2b.co.id/api/webhook/ceisa'),

    'webhook_secret' => env('CEISA_WEBHOOK_SECRET'),

    /*
    | Endpoint relatif terhadap base_url. Sesuaikan dengan dokumentasi H2H
    | resmi yang diberikan Bea Cukai saat onboarding (nilai di bawah adalah
    | pola umum CEISA 4.0 dan WAJIB diverifikasi terhadap dokumen API resmi).
    */
    'endpoints' => [
        'token'  => env('CEISA_TOKEN_ENDPOINT', '/openapi/auth'),
        'submit' => env('CEISA_SUBMIT_ENDPOINT', '/openapi/document'),
        'status' => env('CEISA_STATUS_ENDPOINT', '/openapi/document/status'),
    ],

    /*
    | Margin (detik) sebelum token benar-benar expired agar di-refresh lebih awal.
    */
    'token_refresh_margin' => env('CEISA_TOKEN_REFRESH_MARGIN', 60),

    'timeout' => env('CEISA_HTTP_TIMEOUT', 30),

    /*
    | Tipe dokumen yang didukung. Kode mengikuti jenis dokumen Bea Cukai.
    | MVP fokus ke BC 3.0 (PEB Ekspor).
    */
    'doc_types' => [
        'BC20' => 'BC 2.0 — PIB (Impor)',
        'BC23' => 'BC 2.3 — Pemasukan ke TPB',
        'BC25' => 'BC 2.5 — Pengeluaran dari TPB',
        'BC30' => 'BC 3.0 — PEB (Ekspor)',
    ],

    /*
    | Pemetaan error code CEISA -> pesan yang dapat dibaca operator.
    */
    'error_codes' => [
        '1008' => 'Token tidak valid atau sudah kadaluarsa.',
        '1023' => 'App ID / API Key tidak dikenali atau tidak memiliki akses.',
        '1028' => 'Format payload dokumen tidak valid.',
        '1042' => 'Dokumen ditolak validasi CEISA (data tidak lengkap/duplikat).',
    ],
];
