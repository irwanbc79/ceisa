<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class Document extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTING = 'submitting';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_ERROR = 'error';

    public const JALUR_HIJAU = 'H';

    public const JALUR_KUNING = 'K';

    public const JALUR_MERAH = 'M';

    public const SOURCE_H2H = 'h2h';

    public const SOURCE_ARSIP = 'arsip';

    protected $fillable = [
        'user_id',
        'doc_type',
        'source',
        'nomor_aju',
        'nomor_daftar',
        'id_header',
        'payload',
        'status',
        'jalur',
        'ceisa_response',
        'error_message',
        'submitted_at',
        'response_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'ceisa_response' => 'array',
            'submitted_at' => 'datetime',
            'response_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function webhookLogs(): HasMany
    {
        return $this->hasMany(WebhookLog::class);
    }

    public function isArchived(): bool
    {
        return $this->source === self::SOURCE_ARSIP;
    }

    /**
     * Nama pihak utama (eksportir/importir/pengusaha TPB/pemohon/arsip).
     */
    public function partyName(): ?string
    {
        return data_get($this->payload, 'header.eksportir.nama')
            ?? data_get($this->payload, 'header.importir.nama')
            ?? data_get($this->payload, 'header.pengusaha_tpb.nama')
            ?? data_get($this->payload, 'header.pemohon.nama')
            ?? data_get($this->payload, 'nama_perusahaan');
    }

    /**
     * NPWP pihak utama (format NPWP16) dari payload mana pun.
     */
    public function partyNpwp(): ?string
    {
        return data_get($this->payload, 'header.eksportir.npwp')
            ?? data_get($this->payload, 'header.importir.npwp')
            ?? data_get($this->payload, 'header.pengusaha_tpb.npwp')
            ?? data_get($this->payload, 'header.pemohon.npwp')
            ?? data_get($this->payload, 'npwp');
    }

    /**
     * NITKU (Nomor Identitas Tempat Kegiatan Usaha) bila tersedia di payload.
     */
    public function partyNitku(): ?string
    {
        return data_get($this->payload, 'header.nitku')
            ?? data_get($this->payload, 'nitku');
    }

    public function statusBadgeColor(): string
    {
        return match ($this->status) {
            self::STATUS_ACCEPTED => 'green',
            self::STATUS_SUBMITTED, self::STATUS_SUBMITTING => 'blue',
            self::STATUS_REJECTED, self::STATUS_ERROR => 'red',
            default => 'gray',
        };
    }

    /**
     * Label & warna jalur pemeriksaan pabean (Hijau/Kuning/Merah).
     *
     * @return array{label: string, color: string}|null
     */
    public function jalurInfo(): ?array
    {
        return match ($this->jalur) {
            self::JALUR_HIJAU => ['label' => 'Jalur Hijau', 'color' => 'emerald'],
            self::JALUR_KUNING => ['label' => 'Jalur Kuning', 'color' => 'amber'],
            self::JALUR_MERAH => ['label' => 'Jalur Merah', 'color' => 'rose'],
            default => null,
        };
    }

    /**
     * Ringkasan respon DJBC terakhir (SPPB/BILLING/NPE) dari ceisa_response.
     * Meniru kolom "Nama Respon (No. Surat)" di Portal CEISA 4.0.
     *
     * @return array{nama: string, no_surat: ?string, tanggal: ?Carbon}|null
     */
    public function responseSummary(): ?array
    {
        $resp = $this->ceisa_response;

        if (empty($resp) && ! $this->response_at) {
            return null;
        }

        $nama = data_get($resp, 'nama_respon')
            ?? data_get($resp, 'namaRespon')
            ?? data_get($resp, 'kode_respon')
            ?? data_get($resp, 'kodeRespon')
            ?? data_get($resp, 'jenis_respon')
            ?? data_get($resp, 'response')
            ?? data_get($resp, 'data.nama_respon')
            ?? $this->inferResponseName();

        if (! $nama) {
            return null;
        }

        $noSurat = data_get($resp, 'nomor_surat')
            ?? data_get($resp, 'no_surat')
            ?? data_get($resp, 'nomorSurat')
            ?? data_get($resp, 'nomor_respon')
            ?? data_get($resp, 'nomorRespon')
            ?? data_get($resp, 'data.nomor_surat');

        return [
            'nama' => strtoupper((string) $nama),
            'no_surat' => $noSurat ? (string) $noSurat : null,
            'tanggal' => $this->response_at,
        ];
    }

    /**
     * Tebak nama respon dari status bila CEISA tak mengirim label eksplisit.
     */
    private function inferResponseName(): ?string
    {
        return match ($this->status) {
            self::STATUS_ACCEPTED => 'SPPB',
            self::STATUS_REJECTED => 'PENOLAKAN',
            self::STATUS_SUBMITTED => 'BILLING',
            default => null,
        };
    }

    /**
     * Kode kantor pabean (KdKantor 6-digit) dari payload.
     */
    public function kantorPabeanCode(): ?string
    {
        $code = data_get($this->payload, 'kantor_pabean')
            ?? data_get($this->payload, 'header.kantor_pabean')
            ?? data_get($this->payload, 'header.kode_kantor');

        return $code !== null && $code !== '' ? (string) $code : null;
    }

    /**
     * Label kantor pabean (resolusi dari ceisa_references, di-cache → bebas N+1).
     */
    public function kantorPabeanLabel(): ?string
    {
        $code = $this->kantorPabeanCode();

        if (! $code) {
            return null;
        }

        return static::kantorPabeanMap()[$code] ?? $code;
    }

    /**
     * Peta kode→label kantor pabean, dimuat sekali (cache 1 jam) agar bebas N+1.
     *
     * @return array<string, string>
     */
    public static function kantorPabeanMap(): array
    {
        return Cache::remember('ceisa.kantor_pabean_map', 3600, fn (): array => CeisaReference::query()
            ->where('type', 'kantor_pabean')
            ->pluck('label', 'code')
            ->all());
    }

    /**
     * Tanggal pendaftaran (saat DJBC menetapkan nomor daftar). Meniru
     * kolom "No. & Tgl Pendaftaran" di Portal CEISA 4.0.
     */
    public function tanggalDaftar(): ?Carbon
    {
        if (! $this->nomor_daftar) {
            return null;
        }

        $raw = data_get($this->ceisa_response, 'tanggal_daftar')
            ?? data_get($this->ceisa_response, 'tgl_daftar')
            ?? data_get($this->ceisa_response, 'tanggalDaftar')
            ?? data_get($this->ceisa_response, 'data.tanggal_daftar');

        if ($raw) {
            try {
                return Carbon::parse($raw);
            } catch (\Throwable) {
                // format tak terbaca → jatuh ke response_at
            }
        }

        return $this->response_at;
    }

    /**
     * Timeline riwayat status pabean — meniru tab "Riwayat Status" Portal CEISA 4.0.
     * Memakai riwayat resmi dari respons CEISA bila tersedia; jika tidak, menderivasi
     * milestone dari lifecycle lokal agar tetap informatif.
     *
     * @return array<int, array{label: string, time: ?Carbon, actor: string, done: bool}>
     */
    public function statusTimeline(): array
    {
        $official = $this->extractHistoryArray();

        if ($official !== []) {
            return $official;
        }

        $stages = [[
            'label' => 'Perekaman Dokumen',
            'time' => $this->created_at,
            'actor' => $this->user?->name ?? 'Operator',
            'done' => true,
        ]];

        if ($this->submitted_at) {
            $stages[] = [
                'label' => 'Kirim Dokumen ke CEISA / INSW',
                'time' => $this->submitted_at,
                'actor' => 'SYSTEM',
                'done' => true,
            ];
        }

        if (in_array($this->status, [self::STATUS_SUBMITTED, self::STATUS_ACCEPTED, self::STATUS_REJECTED], true)) {
            $stages[] = [
                'label' => 'Validasi & Penerimaan Dokumen',
                'time' => $this->response_at ?? $this->submitted_at,
                'actor' => 'SYSTEM',
                'done' => true,
            ];
        }

        if ($this->jalur) {
            $stages[] = [
                'label' => 'Penjaluran — '.($this->jalurInfo()['label'] ?? $this->jalur),
                'time' => $this->response_at,
                'actor' => 'SYSTEM',
                'done' => true,
            ];
        }

        if ($this->status === self::STATUS_ACCEPTED) {
            $stages[] = [
                'label' => 'SPPB — Siap Pengeluaran Barang',
                'time' => $this->response_at,
                'actor' => 'SYSTEM',
                'done' => true,
            ];
        } elseif ($this->status === self::STATUS_REJECTED) {
            $stages[] = [
                'label' => 'Penolakan Dokumen (NPP)',
                'time' => $this->response_at,
                'actor' => 'SYSTEM',
                'done' => true,
            ];
        }

        return $stages;
    }

    /**
     * Ambil array riwayat resmi dari ceisa_response (bentuk fleksibel, toleran nama field).
     *
     * @return array<int, array{label: string, time: ?Carbon, actor: string, done: bool}>
     */
    private function extractHistoryArray(): array
    {
        $raw = data_get($this->ceisa_response, 'riwayat')
            ?? data_get($this->ceisa_response, 'riwayatStatus')
            ?? data_get($this->ceisa_response, 'histories')
            ?? data_get($this->ceisa_response, 'history')
            ?? data_get($this->ceisa_response, 'trackingHistory')
            ?? data_get($this->ceisa_response, 'data.riwayat')
            ?? data_get($this->ceisa_response, 'data.histories');

        if (! is_array($raw) || $raw === []) {
            return [];
        }

        $items = [];

        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }

            $label = data_get($row, 'status')
                ?? data_get($row, 'namaStatus')
                ?? data_get($row, 'nama_status')
                ?? data_get($row, 'keterangan')
                ?? data_get($row, 'description');

            if (! $label) {
                continue;
            }

            $items[] = [
                'label' => (string) $label,
                'time' => $this->parseDate(
                    data_get($row, 'waktu')
                    ?? data_get($row, 'tanggal')
                    ?? data_get($row, 'createdDate')
                    ?? data_get($row, 'tanggal_status')
                    ?? data_get($row, 'waktuRekam')
                ),
                'actor' => (string) (data_get($row, 'petugas')
                    ?? data_get($row, 'updatedBy')
                    ?? data_get($row, 'user')
                    ?? data_get($row, 'diperbarui_oleh')
                    ?? 'SYSTEM'),
                'done' => true,
            ];
        }

        return $items;
    }

    /**
     * Riwayat respon DJBC terstruktur (SPPB/BILLING/NPE) untuk tab "Riwayat Respon".
     * Menggabungkan webhook_logs (push) dan respon terakhir (poll), urut terbaru dulu.
     *
     * @return array<int, array{nama: string, no_surat: ?string, tanggal: ?Carbon}>
     */
    public function responseHistory(): array
    {
        $items = [];

        foreach ($this->webhookLogs as $log) {
            $p = $log->payload ?? [];

            $nama = data_get($p, 'nama_respon')
                ?? data_get($p, 'namaRespon')
                ?? data_get($p, 'kode_respon')
                ?? data_get($p, 'response')
                ?? $log->event;

            if (! $nama) {
                continue;
            }

            $items[] = [
                'nama' => strtoupper((string) $nama),
                'no_surat' => data_get($p, 'nomor_surat') ?? data_get($p, 'no_surat') ?? data_get($p, 'nomorSurat'),
                'tanggal' => $log->received_at,
            ];
        }

        $summary = $this->responseSummary();

        if ($summary && ! collect($items)->contains(fn (array $i): bool => $i['nama'] === $summary['nama'])) {
            $items[] = [
                'nama' => $summary['nama'],
                'no_surat' => $summary['no_surat'],
                'tanggal' => $summary['tanggal'],
            ];
        }

        usort($items, fn (array $a, array $b): int => ($b['tanggal']?->timestamp ?? 0) <=> ($a['tanggal']?->timestamp ?? 0));

        return $items;
    }

    /**
     * Riwayat petugas BC (aktor non-SYSTEM dari timeline) untuk tab "Riwayat Petugas".
     *
     * @return array<int, array{petugas: string, kegiatan: string, waktu: ?Carbon}>
     */
    public function petugasHistory(): array
    {
        $items = [];

        foreach ($this->statusTimeline() as $row) {
            $actor = $row['actor'] ?? '';

            if ($actor === '' || strtoupper($actor) === 'SYSTEM' || strtoupper($actor) === 'OPERATOR') {
                continue;
            }

            $items[] = [
                'petugas' => $actor,
                'kegiatan' => $row['label'],
                'waktu' => $row['time'],
            ];
        }

        return $items;
    }

    /**
     * Parse tanggal toleran (CEISA bisa kirim beragam format / null).
     */
    private function parseDate(mixed $raw): ?Carbon
    {
        if (! $raw) {
            return null;
        }

        try {
            return Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Dokumen dapat diubah (edit) selama belum terkirim/diterima CEISA.
     * Dokumen arsip (catatan historis lokal) selalu dapat diperbaiki.
     */
    public function isEditable(): bool
    {
        return $this->isArchived()
            || in_array($this->status, [self::STATUS_DRAFT, self::STATUS_ERROR], true);
    }

    /**
     * Dokumen dapat dihapus. Dokumen H2H yang sudah hidup di DJBC
     * (submitted/accepted/submitting) dipertahankan demi jejak audit.
     */
    public function canBeDeleted(): bool
    {
        if ($this->isArchived()) {
            return true;
        }

        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_ERROR, self::STATUS_REJECTED], true);
    }

    /**
     * Ratakan payload bersarang menjadi field datar form wizard (kebalikan toCeisaPayload).
     * Dipakai untuk pre-fill form saat edit dokumen H2H.
     *
     * @return array<string, mixed>
     */
    public function toFormData(): array
    {
        $h = data_get($this->payload, 'header', []);
        $barang = data_get($this->payload, 'barang', []);

        $common = [
            'doc_type' => $this->doc_type,
            'nomor_aju' => $this->nomor_aju,
        ];

        $data = match ($this->doc_type) {
            'BC30' => [
                'kantor_muat' => data_get($h, 'kantor_muat'),
                'jenis_ekspor' => data_get($h, 'jenis_ekspor'),
                'kategori_ekspor' => data_get($h, 'kategori_ekspor'),
                'cara_dagang' => data_get($h, 'cara_dagang'),
                'cara_bayar' => data_get($h, 'cara_bayar'),
                'komoditi' => data_get($h, 'komoditi'),
                'curah' => data_get($h, 'curah'),
                'nama_eksportir' => data_get($h, 'eksportir.nama'),
                'npwp_eksportir' => data_get($h, 'eksportir.npwp'),
                'alamat_eksportir' => data_get($h, 'eksportir.alamat'),
                'nama_penerima' => data_get($h, 'penerima.nama'),
                'negara_tujuan' => data_get($h, 'penerima.negara'),
                'alamat_penerima' => data_get($h, 'penerima.alamat'),
                'cara_angkut' => data_get($h, 'pengangkutan.cara_angkut'),
                'nama_sarana' => data_get($h, 'pengangkutan.sarana_angkut'),
                'voy_flight' => data_get($h, 'pengangkutan.voy_flight'),
                'pelabuhan_muat' => data_get($h, 'pengangkutan.pelabuhan_muat'),
                'pelabuhan_bongkar' => data_get($h, 'pengangkutan.pelabuhan_bongkar'),
                'pelabuhan_tujuan' => data_get($h, 'pengangkutan.pelabuhan_tujuan'),
                'tanggal_ekspor' => data_get($h, 'pengangkutan.tanggal_ekspor'),
                'kode_valuta' => data_get($h, 'valuta'),
                'ndpbm' => data_get($h, 'ndpbm'),
                'incoterm' => data_get($h, 'incoterm'),
                'nilai_fob' => data_get($h, 'nilai_fob'),
                'freight' => data_get($h, 'freight'),
                'asuransi_jenis' => data_get($h, 'asuransi.jenis'),
                'nilai_asuransi' => data_get($h, 'asuransi.nilai'),
                'bruto' => data_get($h, 'bruto'),
                'bank_devisa' => data_get($h, 'bank_devisa'),
                'cara_pembayaran' => data_get($h, 'cara_pembayaran'),
                'pernyataan_nama' => data_get($h, 'pernyataan.nama'),
                'pernyataan_jabatan' => data_get($h, 'pernyataan.jabatan'),
                'pernyataan_kota' => data_get($h, 'pernyataan.kota'),
            ],
            'BC20', 'BC24' => [
                'nama_importir' => data_get($h, 'importir.nama'),
                'npwp_importir' => data_get($h, 'importir.npwp'),
                'alamat_importir' => data_get($h, 'importir.alamat'),
                'nib_importir' => data_get($h, 'importir.nib'),
                'jenis_api' => data_get($h, 'importir.jenis_api'),
                'nama_pemasok' => data_get($h, 'pemasok.nama'),
                'negara_pemasok' => data_get($h, 'pemasok.negara'),
                'jenis_impor' => data_get($h, 'jenis_impor'),
                'cara_bayar' => data_get($h, 'cara_bayar'),
                'pelabuhan_muat' => data_get($h, 'pengangkutan.pelabuhan_muat'),
                'pelabuhan_bongkar' => data_get($h, 'pengangkutan.pelabuhan_bongkar'),
                'cara_angkut' => data_get($h, 'pengangkutan.cara_angkut'),
                'nama_sarana' => data_get($h, 'pengangkutan.sarana_angkut'),
                'voy_flight' => data_get($h, 'pengangkutan.voy_flight'),
                'kode_bendera' => data_get($h, 'pengangkutan.bendera'),
                'kode_tps' => data_get($h, 'pengangkutan.tps'),
                'tanggal_tiba' => data_get($h, 'pengangkutan.tanggal_tiba'),
                'kode_valuta' => data_get($h, 'valuta'),
                'ndpbm' => data_get($h, 'ndpbm'),
                'incoterm' => data_get($h, 'incoterm'),
                'nilai_cif' => data_get($h, 'nilai_cif'),
                'freight' => data_get($h, 'freight'),
                'asuransi_jenis' => data_get($h, 'asuransi_jenis'),
                'nilai_asuransi' => data_get($h, 'asuransi'),
                'bruto' => data_get($h, 'bruto'),
                'cara_pembayaran' => data_get($h, 'cara_pembayaran'),
                'pernyataan_nama' => data_get($h, 'pernyataan.nama'),
                'pernyataan_jabatan' => data_get($h, 'pernyataan.jabatan'),
                'pernyataan_kota' => data_get($h, 'pernyataan.kota'),
            ],
            'TPB' => [
                'nama_tpb' => data_get($h, 'pengusaha_tpb.nama'),
                'npwp_tpb' => data_get($h, 'pengusaha_tpb.npwp'),
                'alamat_tpb' => data_get($h, 'pengusaha_tpb.alamat'),
                'jenis_tpb' => data_get($h, 'jenis_tpb'),
                'tujuan_tpb' => data_get($h, 'tujuan_pengiriman'),
                'dokumen_referensi' => data_get($h, 'dokumen_referensi'),
                'kode_valuta' => data_get($h, 'valuta'),
                'nilai_barang' => data_get($h, 'nilai_barang'),
                'kode_kantor' => data_get($h, 'kode_kantor'),
                'cara_angkut' => data_get($h, 'pengangkutan.cara_angkut'),
                'nama_sarana' => data_get($h, 'pengangkutan.sarana_angkut'),
                'voy_flight' => data_get($h, 'pengangkutan.voy_flight'),
                'kode_bendera' => data_get($h, 'pengangkutan.bendera'),
            ],
            'RUSH' => [
                'nama_pemohon' => data_get($h, 'pemohon.nama'),
                'npwp_pemohon' => data_get($h, 'pemohon.npwp'),
                'alamat_pemohon' => data_get($h, 'pemohon.alamat'),
                'nama_sarana_pengangkut' => data_get($h, 'pengangkutan.sarana'),
                'nomor_flight' => data_get($h, 'pengangkutan.flight_no'),
                'nomor_awb_bl' => data_get($h, 'dokumen_pengangkutan.awb_bl'),
                'tanggal_awb_bl' => data_get($h, 'dokumen_pengangkutan.tanggal'),
                'alasan_segera' => data_get($h, 'alasan_rush_handling'),
                'jumlah_kemasan' => data_get($h, 'kemasan.jumlah'),
                'jenis_kemasan' => data_get($h, 'kemasan.jenis'),
                'kode_kantor' => data_get($h, 'kode_kantor'),
                'kode_bendera' => data_get($h, 'pengangkutan.bendera'),
                'cara_angkut' => data_get($h, 'pengangkutan.cara_angkut'),
            ],
            default => [],
        };

        $data['barang'] = array_map(static fn ($b): array => [
            'hs_code' => data_get($b, 'hs_code', ''),
            'uraian' => data_get($b, 'uraian', ''),
            'merk' => data_get($b, 'merk', ''),
            'tipe' => data_get($b, 'tipe', ''),
            'ukuran' => data_get($b, 'ukuran', ''),
            'negara_asal' => data_get($b, 'negara_asal', ''),
            'daerah_asal' => data_get($b, 'daerah_asal', ''),
            'jumlah_satuan' => data_get($b, 'jumlah_satuan', ''),
            'kode_satuan' => data_get($b, 'kode_satuan', ''),
            'jumlah_kemasan' => data_get($b, 'jumlah_kemasan', ''),
            'kode_kemasan' => data_get($b, 'kode_kemasan', ''),
            'netto' => data_get($b, 'netto', ''),
            'volume' => data_get($b, 'volume', ''),
            'nilai_fob' => data_get($b, 'nilai_fob', ''),
            'nilai_cif' => data_get($b, 'nilai_cif', ''),
            'nilai_barang' => data_get($b, 'nilai_barang', ''),
        ], is_array($barang) ? $barang : []);

        // Buang nilai null agar tidak menimpa default form dengan kosong.
        $data = array_filter($data, static fn ($v) => $v !== null);

        return array_merge($common, $data);
    }

    /**
     * Data ringkas untuk tampilan cepat (quick view) dokumen di modal popup.
     *
     * @return array<string, mixed>
     */
    public function quickViewData(): array
    {
        $jalur = $this->jalurInfo();

        return [
            'id' => $this->id,
            'doc_type' => $this->doc_type,
            'doc_type_label' => config('ceisa.doc_types')[$this->doc_type] ?? $this->doc_type,
            'nomor_aju' => $this->nomor_aju,
            'nomor_daftar' => $this->nomor_daftar,
            'id_header' => $this->id_header,
            'status' => $this->status,
            'jalur' => $jalur['label'] ?? null,
            'jalur_color' => $jalur['color'] ?? null,
            'source' => $this->isArchived() ? 'Arsip' : 'H2H',
            'party_name' => $this->partyName(),
            'party_npwp' => $this->partyNpwp(),
            'uraian' => data_get($this->payload, 'uraian'),
            'created_at' => $this->created_at?->format('d/m/Y H:i'),
            'show_url' => route('documents.show', $this),
            'editable' => $this->isEditable(),
            'edit_url' => route('documents.edit', $this),
        ];
    }

    /**
     * Field datar untuk pre-fill form arsip (rekam manual) saat edit.
     *
     * @return array<string, mixed>
     */
    public function toArchiveFormData(): array
    {
        return [
            'doc_type' => $this->doc_type,
            'nomor_aju' => $this->nomor_aju,
            'nomor_daftar' => $this->nomor_daftar,
            'status' => $this->status,
            'jalur' => $this->jalur,
            'tanggal_dokumen' => optional($this->submitted_at)->toDateString()
                ?? data_get($this->payload, 'tanggal_dokumen'),
            'nama_perusahaan' => data_get($this->payload, 'nama_perusahaan'),
            'npwp' => data_get($this->payload, 'npwp'),
            'kantor_pabean' => data_get($this->payload, 'kantor_pabean'),
            'kode_valuta' => data_get($this->payload, 'valuta', 'USD'),
            'nilai' => data_get($this->payload, 'nilai'),
            'uraian' => data_get($this->payload, 'uraian'),
            'keterangan' => data_get($this->payload, 'keterangan'),
        ];
    }
}
