# CEISA 4.0 H2H — Referensi Integrasi Terverifikasi

> Ground-truth dari Beacukai Developer Portal (portal.beacukai.go.id) +
> probe live akun H2H **PT Mora Multi Berkah (PPJK)** pada **2026-06-29**.
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
- 🟡 Submit dokumen: `POST /v2/openapi/document?isFinal={bool}&isRevision={bool}` (base ✅, sub-path `/document` asumsi)
- 🟡 Status: `GET /v2/openapi/status/{nomorAju}` atau `?idPerusahaan={NPWP}`
- 🟡 File: `/v2/openapi/file/dokumen`, `/v2/openapi/file/barang`
- **TODO**: konfirmasi sub-path submit/status dari Swagger "API for Host To Host services" (`/v2/openapi`, versi 2.0, Production).

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

### Gap fungsional builder BC20 (belum schema-breaking, tapi perlu untuk produksi)
1. `barangTarif` baru BM saja — produksi butuh BM+PPN+PPH.
2. Entitas PPJK (kode `4`) belum di-generate padahal Mora = PPJK.
3. Dokumen House-BL/AWB (705) belum disertakan (baru invoice 380).
4. `kodeTps` default kosong — wajib diisi dari data.
5. `kontainer` kosong (jumlahKontainer 0) — untuk FCL perlu diisi.

## Error & REST Codes
- Error validasi: 1008 (enum), 1023 (pattern), 1028 (mandatory), 1042 (const) — sudah lengkap di config.
- REST/HTTP: standar RFC2616. **Gap penanganan kita**: 401 belum auto re-login+retry; pesan HTTP masih generik (belum di-implement).
