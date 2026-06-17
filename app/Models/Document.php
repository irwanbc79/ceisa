<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

        $common = ['doc_type' => $this->doc_type];

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
                'nama_pemasok' => data_get($h, 'pemasok.nama'),
                'negara_pemasok' => data_get($h, 'pemasok.negara'),
                'pelabuhan_muat' => data_get($h, 'pengangkutan.pelabuhan_muat'),
                'pelabuhan_bongkar' => data_get($h, 'pengangkutan.pelabuhan_bongkar'),
                'kode_valuta' => data_get($h, 'valuta'),
                'nilai_cif' => data_get($h, 'nilai_cif'),
                'cara_pembayaran' => data_get($h, 'cara_pembayaran'),
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
