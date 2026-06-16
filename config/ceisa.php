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
    | Host gateway resmi (lihat openapi.beacukai.go.id / ceisa40.gitbook.io).
    | Auth (login & refresh) di /nle-oauth/v1, layanan Pabean (dokumen, status) di /openapi.
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
    | Terverifikasi dari dokumentasi resmi PIA-CEISA40 (ceisa40.gitbook.io/pia-ceisa40):
    |   - token  : POST {host}/nle-oauth/v1/user/login (body: username+password)
    |   - refresh: POST {host}/nle-oauth/v1/user/update-token (header: Authorization refresh_token)
    |   - submit : POST {host}/openapi/document
    |   - status : GET  {host}/openapi/status/{nomorAju} atau ?idPerusahaan={NPWP}
    */
    'endpoints' => [
        'token' => env('CEISA_TOKEN_ENDPOINT', '/nle-oauth/v1/user/login'),
        'refresh_token' => env('CEISA_REFRESH_ENDPOINT', '/nle-oauth/v1/user/update-token'),
        'submit' => env('CEISA_SUBMIT_ENDPOINT', '/openapi/document'),
        'status' => env('CEISA_STATUS_ENDPOINT', '/openapi/status'),
        'download_respon' => env('CEISA_DOWNLOAD_ENDPOINT', '/openapi/download-respon'),
        'cetak_formulir' => env('CEISA_CETAK_ENDPOINT', '/openapi/respon/cetak-formulir'),
        'billing' => env('CEISA_BILLING_ENDPOINT', '/openapi/respon/billing'),
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
