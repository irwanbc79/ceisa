# CEISA 4.0 H2H — Referensi Integrasi Terverifikasi

> Ground-truth dari Beacukai Developer Portal (portal.beacukai.go.id) +
> probe live akun H2H **PT Mora Multi Berkah (PPJK)** pada **2026-06-29**
> dan **2026-07-02** (`php artisan ceisa:probe-status`, log lengkap di
> `storage/logs/ceisa-probe.log` server).
> Status legend: ✅ diverifikasi live · 🟡 base benar, sub-path masih asumsi.

## Gateway & Base URL
- Production: `https://apis-gw.beacukai.go.id`
- Development/Sandbox: `https://apisdev-gw.beacukai.go.id`
  - ⚠️ Akun H2H Mora **hanya hidup di PRODUCTION**. Login ke sandbox → `Invalid user credentials`. Semua pilot di production gateway → WAJIB `isFinal=false` (draft).
- Base layanan H2H (dokumen, status, kurs, file): **`/v2/openapi`** ✅
- Base autentikasi: **`/v1/openapi-auth`** ✅

## Autentikasi (OAuth2 Client Credentials)
- ✅ Login: `POST /v1/openapi-auth/user/login`
  - Header: `beacukai-api-key: {API_KEY}` + `Content-Type: application/json`
  - Body: `{"username","password"}`
  - **`id_platform` TIDAK dipakai** pada flow resmi.
  - Response: `{status, message, item:{access_token, expires_in, refresh_token, refresh_expires_in, token_type:"Bearer", ...}}`
  - Observasi live: `expires_in=7200` (2 jam), `refresh_expires_in=14400` (4 jam). (Doc portal menyebut access 5 menit / refresh 24 jam — kode baca `expires_in` dari response, jadi adaptif.)
- 🟡 Refresh: `POST /v1/openapi-auth/user/update-token` (sub-path inferred; gagal → fallback login penuh, jadi non-fatal)
- Semua call layanan: `Authorization: Bearer {access_token}` + `beacukai-api-key: {API_KEY}`

## Endpoint Layanan
- ✅ Kurs/NDPBM: `GET /v2/openapi/kurs/{VALUTA}` → `{"status":"true","data":[{"nilaiKurs":"17781"}]}` (USD, live)
- ✅ Submit dokumen: `POST /v2/openapi/document?isFinal={bool}&isRevision={bool}` — path TERKONFIRMASI live 2026-07-02: `GET /v2/openapi/document` → **405 `{"Exception":"Method not allowed : get"}`** (path ada, hanya menerima POST).
- ✅ Status: `GET /v2/openapi/status?idPerusahaan={NPWP15}` — TERVERIFIKASI live 2026-07-02, HTTP 200 dengan data riil.
  - ⚠️ `idPerusahaan` **WAJIB** (tanpa param → 500 "Required String parameter 'idPerusahaan' is not present"; varian `?npwp=` juga ditolak → param name persis `idPerusahaan`).
  - ⚠️ NPWP **HARUS 15 digit**. 16 digit berawalan 0 → 200 `{"status":"Failed","message":"Data Perusahaan Tidak Sesuai"}`. Kode menormalkan otomatis (`CeisaService::npwp15()`).
  - ⚠️ Varian path `/status/{nomorAju}` TIDAK didukung — endpoint mengembalikan SEMUA dokumen perusahaan; filter per-aju dilakukan di sisi klien (`queryDocumentStatus`).
- 🟡 File: `/v2/openapi/file/dokumen`, `/v2/openapi/file/barang` (cocok dgn Postman resmi, belum diprobe live)

### Skema respons status (live 2026-07-02, dokumen BC 3.0 riil `000030MOT83720260525000009`)
```json
{
  "status": "Success", "message": "Data Ditemukan",
  "dataStatus": [
    {"nomorAju":"...", "nomorDaftar":"003574", "tanggalDaftar":"01-07-2026",
     "kodeProses":"400", "waktuStatus":"2026-07-01 13:13:00.03",
     "keterangan":"Pemeriksaan Dokumen", "kodeDokumen":"30"}
  ],
  "dataRespon": [
    {"nomorAju":"...", "kodeRespon":"3015", "nomorDaftar":"003574",
     "nomorRespon":"003570/KBC.0207/2026", "keterangan":"NPE",
     "pdf":"respon/2026/7/1/....pdf", "kodeDokumen":"30", "billingPungutans":null}
  ]
}
```
Catatan penting:
- `dataStatus` = TIMELINE (banyak baris per nomorAju), **urutan TIDAK kronologis** — sort by `waktuStatus`.
- TIDAK ada field `status`/`jalur` di dataStatus — status dibaca dari `keterangan`+`kodeProses`.
- kodeProses terobservasi: 001 Perekaman Dokumen · 104 Kirim Dokumen Ke INSW · 105 · 111 Proses Validasi · 210 Payment Verification · 230 Siap Jalur · 240 Penjaluran · 400 Pemeriksaan Dokumen · 960 Perekaman Perbaikan Portal.
- `dataRespon.kodeRespon` 3015 = **NPE** (Nota Pelayanan Ekspor, final ekspor) — path PDF di key `pdf` (dipakai endpoint download-respon).

## API Gallery (modul tersedia)
- API for Host To Host services (`/v2/openapi`, v2.0, Production) — Manifes, H2H, Barang Kiriman, TPB, PLB, Pabean
- openapi-auth (`/v1/openapi-auth`) — autentikasi
- openapi-manifes (`/v1/openapi-manifes`) — manifes/BC 1.1
- H2H Cukai (`/v1/...`) — cukai

## Identitas Pengguna Jasa (kredensial → DB `ceisa_credentials`, terenkripsi)
- Perusahaan: PT Mora Multi Berkah — peran **PPJK** (kode entitas `4` di payload)
- NPWP: `960208833125000` · NITKU: `0960208833125000000000`
- Username: `mayank.harahap` (Eka Mayang Sari Harahap)
- App ID & API Key & password → JANGAN commit; isi via halaman Pengaturan CEISA.
- Webhook portal: aktif → `https://ceisa.m2b.co.id/api/webhook/ceisa`
- IP Whitelist: OFF (biarkan, karena call keluar dari IP server, bukan IP user).

## Kontrak Validasi BC 2.0 (JSON Schema "Kirim Dokumen BC 20")
Top-level wajib: asuransi, bruto, cif, kodeJenisImpor, freight, jabatanTtd,
jumlahKontainer, kodeCaraBayar, kodeKantor, kodePelMuat, kodePelTujuan, kodeTps,
kodeTutupPu, kodeValuta, kotaTtd, namaTtd, ndpbm, netto, nomorAju, tanggalTtd,
tanggalTiba, biayaTambahan, biayaPengurang, barang, entitas, kemasan, dokumen, pengangkut.

Per item `barang` wajib: asuransi, cif, fob, freight, hargaSatuan, jumlahKemasan,
jumlahSatuan, kodeJenisKemasan, kodeSatuanBarang, merk(≥2 char), posTarif,
saldoAkhir, saldoAwal, seriBarang, tipe(≥2 char), uraian, metodePenentuanNilai,
alasanMetodePenentuanNilai(boleh null), statementPerbedaanHarga, **barangTarif**, **barangVd**.

Penting:
- ⚠️ Nama field schema = `barangTarif`/`barangVd`/`barangDokumen` (BUKAN `ttBarangTarifs` dst. yang muncul di contoh "Payload" portal). **Builder kita ikut schema.** ✅
- `kodeDokumen` const "20" (BC20), entitas tuple: importir(1)/pemilik(7)/pengirim(9)/penjual(10)/pemusatan(11)/PPJK(4).
- `nomorAju`: 26 digit `^[A-Za-z0-9]{26}$` = 4 kantor + 2 dokumen + 6 unik + 8 tgl(YYYYMMDD) + 6 urut.

### Gap fungsional builder BC20 (status per 2026-07-02)
1. ~~`barangTarif` baru BM saja~~ ✅ FIXED (94ea14e): BM+PPN+PPH (default PPN 11% / PPh 2.5%, override via `tarif_bm/ppn/pph`).
2. ~~Entitas PPJK (kode `4`) belum di-generate~~ ✅ FIXED (94ea14e): tergenerate bila `header.ppjk` ada.
3. ~~Dokumen House-BL/AWB (705) belum disertakan~~ ✅ FIXED (94ea14e): 705 laut / 740 udara bila `header.dokumen_pengangkutan.awb_bl` ada.
4. `kodeTps` default kosong — wajib diisi dari data (field wizard belum ada). ⏳
5. `kontainer` kosong (jumlahKontainer 0) — untuk FCL perlu diisi. ⏳ (step kontainer wizard sudah ada)
6. TPB & RUSH builder masih fallback dummy kantor `040100` — gating UI DITUNDA (keputusan 2026-07-02).

## Error & REST Codes
- Error validasi: 1008 (enum), 1023 (pattern), 1028 (mandatory), 1042 (const) — sudah lengkap di config.
- REST/HTTP: standar RFC2616. ~~401 belum auto re-login+retry~~ ✅ FIXED (94ea14e): `authorizedRequest()` re-login + retry sekali; `httpStatusMessage()` pesan Indonesia per kode.

## Status operasional (2026-07-02)
- Webhook `https://ceisa.m2b.co.id/api/webhook/ceisa` terdaftar di portal DJBC; `CEISA_WEBHOOK_SECRET` di server KOSONG (webhook TIDAK tertolak). `webhook_logs` masih 0 baris — wajar, belum pernah submit `isFinal=true`.
- Ada 1 dokumen BC 3.0 RIIL milik Mora di CEISA (direkam via portal): `000030MOT83720260525000009`, nomorDaftar 003574, respon NPE — bisa ditarik ke aplikasi via tombol Sinkronisasi (documents.sync).
