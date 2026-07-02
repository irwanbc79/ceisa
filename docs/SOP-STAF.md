# SOP Staf — Aplikasi CEISA H2H M2B (ceisa.m2b.co.id)

> Panduan ringkas untuk staf operator PT Mora Multi Berkah.
> Satu halaman — alur harian dari login sampai dokumen selesai.

## 1. Akses & Akun
- Buka **https://ceisa.m2b.co.id** → login dengan email & password yang diberikan admin.
- Lupa password / akun terkunci → hubungi admin (admin bisa reset dari menu **Pengguna**).
- Kredensial portal CEISA (username/password/API key Bea Cukai) **dikelola admin** —
  operator TIDAK perlu mengisinya. Cek status di menu **Pengaturan CEISA**
  (harus "terpasang"; ada tombol **Uji Koneksi** bila ragu).

## 2. Membuat Dokumen (PEB/PIB)
1. Menu **Buat Dokumen** → pilih jenis: **BC 3.0** (ekspor) atau **BC 2.0/2.4** (impor).
   ⚠ TPB & Rush Handling belum siap produksi — jangan dipakai dulu.
2. Ikuti wizard 5 langkah: Header → Entitas → Barang → Dokumen/Kontainer → Review.
   Field dropdown (negara, satuan, kantor pabean, dll.) sudah berisi kode resmi DJBC.
3. **Simpan Draft** dulu bila data belum lengkap — draft bisa diedit kapan pun
   (tombol **Ubah**) dan tidak menyentuh sistem Bea Cukai sama sekali.

## 3. Validasi Sebelum Kirim
- Di halaman detail dokumen, klik **Validasi AI** — sistem memeriksa
  kelengkapan & kewajaran data (HS code, netto vs jumlah, harga per kg, entitas).
- Perbaiki semua temuan level *error* sebelum submit. Temuan *warning* = periksa ulang.

## 4. Mengirim ke Bea Cukai (Submit)
- Tombol **Kirim ke CEISA** di halaman detail. Nomor aju digenerate otomatis.
- Status berubah: `draft → submitting → submitted`. Bila **error**, baca pesannya
  (kode 1008/1023/1028/1042 = data tidak valid — perbaiki lalu kirim ulang).
- Dokumen yang perlu perbaikan setelah respon DJBC → **Kirim Pembetulan**.

## 5. Memantau Status & Respon
- **Daftar Dokumen** = semua dokumen perusahaan (terlihat oleh seluruh tim).
  Kolom Respon DJBC menampilkan SPPB/NPE/Billing/Penolakan + nomor & tanggal surat.
- Tombol **Perbarui Status** (di detail) menarik status terbaru dari CEISA.
- Tombol **Sinkronisasi** (di Daftar Dokumen) menarik SEMUA dokumen perusahaan
  dari CEISA — termasuk yang direkam lewat portal Bea Cukai.
  ⚠ Bila hasil "0 dokumen" padahal seharusnya ada, coba lagi beberapa menit
  kemudian (server CEISA kadang tidak konsisten).
- Jalur pemeriksaan: **Hijau** = langsung; **Kuning** = periksa dokumen;
  **Merah** = periksa fisik.
- PDF respon (SPPB/NPE/Billing) bisa diunduh dari halaman detail.

## 6. Notifikasi & Manifes
- Menu **Notifikasi** = pemberitahuan DJBC (3 tab: Respon / Formulir / Informasi).
- Menu **Monitoring Manifes** = data BC 1.1 kedatangan/keberangkatan sarana pengangkut
  (tarik data dengan tombol **Sinkronisasi Manifes**).

## 7. Aturan Tim
- Semua staf melihat & boleh mengerjakan semua dokumen perusahaan;
  sistem mencatat siapa membuat apa (audit).
- Dokumen yang SUDAH terkirim (submitted/accepted) tidak bisa dihapus — by design.
- Jangan bagikan password akun; admin membuatkan akun per orang.

## Kontak
- Kendala teknis aplikasi → admin M2B (menu Pengguna → lihat siapa admin).
- Kendala di sisi Bea Cukai (akun portal, respon dokumen) → hubungi kantor pabean terkait.
