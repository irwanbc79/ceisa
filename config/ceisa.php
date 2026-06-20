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
    | Host gateway resmi (Beacukai Developer Portal / openapi.beacukai.go.id / ceisa40.gitbook.io).
    | Auth (login & refresh) di /nle-oauth/v1, layanan Pabean (dokumen, status) di /openapi.
    |
    | Environment gateway NLE-CEISA 4.0:
    |   - Production         : https://apis-gw.beacukai.go.id
    |   - Development/Staging: https://apisdev-gw.beacukai.go.id
    */
    'base_url' => rtrim(env('CEISA_BASE_URL', 'https://apis-gw.beacukai.go.id'), '/'),

    /*
    | Header API Key wajib pada SEMUA request (auth maupun layanan).
    | Dokumentasi resmi tidak konsisten antar-versi soal nama header:
    |   - PIA-CEISA40 gitbook menyebut "Beacukai-Api-Key"
    |   - Beacukai Developer Portal (gateway NLE) menyebut "nle-api-key"
    | Untuk memaksimalkan kompatibilitas onboarding, key dikirim pada SEMUA nama
    | header di daftar ini (gateway mengabaikan header yang tak dikenal).
    | Nilai key disimpan per-user terenkripsi di kolom api_key tabel ceisa_credentials.
    */
    'api_key_headers' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('CEISA_API_KEY_HEADERS', 'Beacukai-Api-Key,nle-api-key'))
    ))),

    /*
    | Header Origin: domain asal sistem klien. Dilampirkan pada semua request
    | sesuai standard headers Beacukai Developer Portal untuk H2H.
    */
    'origin' => env('CEISA_ORIGIN', env('APP_URL', 'https://ceisa.m2b.co.id')),

    'app_id' => env('CEISA_APP_ID'),

    'api_key' => env('CEISA_API_KEY'),

    'id_platform' => env('CEISA_ID_PLATFORM'),

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
        'upload_dokap' => env('CEISA_UPLOAD_DOKAP_ENDPOINT', '/v2/openapi/file/dokumen'),
        'upload_gambar' => env('CEISA_UPLOAD_GAMBAR_ENDPOINT', '/v2/openapi/file/barang'),
        'upload_dokap_npd' => env('CEISA_UPLOAD_NPD_ENDPOINT', '/v2/openapi/file/upload-dokap-npd'),
    ],

    /*
    | Sinkronisasi master data referensi (cron `ceisa:sync-references`).
    | Key = tipe internal pada tabel ceisa_references (lihat CeisaReference::forWizard).
    | Value = path resource API referensi CEISA (relatif base_url).
    |
    | ⚠ Path persisnya berada di Swagger "openapi" (Pabean) yang butuh auth untuk
    | diunduh — ISI/override via .env saat sudah diketahui. Tipe dengan path kosong
    | otomatis dilewati (data dasarnya tetap tersedia via CeisaReferenceSeeder).
    */
    'reference_endpoints' => array_filter([
        'negara' => env('CEISA_REF_NEGARA'),
        'pelabuhan' => env('CEISA_REF_PELABUHAN'),
        'valuta' => env('CEISA_REF_VALUTA'),
        'satuan' => env('CEISA_REF_SATUAN'),
        'kemasan' => env('CEISA_REF_KEMASAN'),
        'kantor_pabean' => env('CEISA_REF_KANTOR'),
        'incoterm' => env('CEISA_REF_INCOTERM'),
        'cara_angkut' => env('CEISA_REF_CARA_ANGKUT'),
        'hs_code' => env('CEISA_REF_HS'),
    ]),

    /*
    | Margin (detik) sebelum token benar-benar expired agar di-refresh lebih awal.
    */
    'token_refresh_margin' => env('CEISA_TOKEN_REFRESH_MARGIN', 60),

    /*
    | Umur token fallback (detik) bila response login tak menyertakan expires_in.
    | Access Token CEISA 4.0 berumur ~5 menit.
    */
    'token_ttl_fallback' => env('CEISA_TOKEN_TTL_FALLBACK', 300),

    /*
    | Query parameter default saat submit dokumen ke {host}/openapi/document:
    |   - isFinal=true  -> data langsung disubmit ke sistem Bea Cukai (dapat respons).
    |     isFinal=false -> data hanya masuk portal web CEISA sebagai DRAFT (testing).
    |   - isRevision    -> dipakai bila mengirim data perbaikan / BCF (khusus BC 3.0 & TPB).
    | Catatan: "draft lokal" aplikasi ini TIDAK menyentuh CEISA sama sekali; ketika
    | submit() dipanggil berarti pengiriman sungguhan -> default is_final true.
    */
    'submit_is_final_default' => env('CEISA_SUBMIT_IS_FINAL', true),

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
