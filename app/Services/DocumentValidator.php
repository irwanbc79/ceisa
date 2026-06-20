<?php

namespace App\Services;

use App\Models\Document;
use App\Services\AI\AiException;
use App\Services\AI\HybridAiClient;

/**
 * Validasi cerdas dokumen kepabeanan SEBELUM dikirim ke CEISA.
 *
 * Dua lapisan:
 *   1. Aturan deterministik (selalu jalan): HS code, netto/jumlah, harga/kg,
 *      kelengkapan field — tidak butuh AI.
 *   2. Analisis AI hybrid (Gemini/DeepSeek via failover): mendeteksi
 *      anomali yang sulit diaturkan (harga tak wajar vs historis, HS vs uraian,
 *      konsistensi data). Bila semua provider gagal, hasil aturan tetap tampil.
 */
class DocumentValidator
{
    public function __construct(
        protected ?HybridAiClient $ai = null,
    ) {
        $this->ai ??= HybridAiClient::fromConfig();
    }

    /**
     * @return array{
     *   rule_findings: array<int, array{level: string, field: ?string, message: string}>,
     *   ai_findings: array<int, array{level: string, field: ?string, message: string}>,
     *   provider: ?string,
     *   ai_error: ?string
     * }
     */
    public function validate(Document $document): array
    {
        $ruleFindings = $this->ruleChecks($document);

        $aiFindings = [];
        $provider = null;
        $aiError = null;

        if (config('ai.enabled') && $this->ai->hasConfiguredProvider()) {
            try {
                [$provider, $aiFindings] = $this->aiChecks($document);
            } catch (AiException $e) {
                $aiError = $e->getMessage();
            }
        } elseif (config('ai.enabled')) {
            $aiError = 'Belum ada provider AI yang dikonfigurasi (isi GEMINI_API_KEY / DEEPSEEK_API_KEY).';
        }

        return [
            'rule_findings' => $ruleFindings,
            'ai_findings' => $aiFindings,
            'provider' => $provider,
            'ai_error' => $aiError,
        ];
    }

    /**
     * Lapisan aturan deterministik.
     *
     * @return array<int, array{level: string, field: ?string, message: string}>
     */
    protected function ruleChecks(Document $document): array
    {
        $f = [];
        $payload = $document->payload ?? [];
        $valueKey = match ($document->doc_type) {
            'BC30' => 'nilai_fob',
            'BC20', 'BC24' => 'nilai_cif',
            default => 'nilai_barang',
        };

        $barang = data_get($payload, 'barang', []);
        if (empty($barang)) {
            $f[] = ['level' => 'error', 'field' => 'barang', 'message' => 'Dokumen tidak memiliki pos barang.'];
        }

        foreach ($barang as $i => $item) {
            $seri = data_get($item, 'seri', $i + 1);
            $hs = preg_replace('/\D/', '', (string) data_get($item, 'hs_code'));
            if (strlen($hs) !== 8) {
                $f[] = ['level' => 'warning', 'field' => "barang #{$seri} HS", 'message' => 'Kode HS sebaiknya 8 digit (BTKI). Saat ini: '.(data_get($item, 'hs_code') ?: 'kosong').'.'];
            }

            $netto = (float) data_get($item, 'netto', 0);
            $qty = (float) data_get($item, 'jumlah_satuan', 0);
            $nilai = (float) data_get($item, $valueKey, 0);

            if ($netto <= 0) {
                $f[] = ['level' => 'warning', 'field' => "barang #{$seri} netto", 'message' => 'Netto 0 / kosong.'];
            }
            if ($qty <= 0) {
                $f[] = ['level' => 'warning', 'field' => "barang #{$seri} jumlah", 'message' => 'Jumlah satuan 0 / kosong.'];
            }
            if ($nilai <= 0) {
                $f[] = ['level' => 'warning', 'field' => "barang #{$seri} nilai", 'message' => 'Nilai barang 0 / kosong.'];
            }

            // Harga per kg sebagai sanity check kasar.
            if ($netto > 0 && $nilai > 0) {
                $perKg = $nilai / $netto;
                if ($perKg < 0.05) {
                    $f[] = ['level' => 'warning', 'field' => "barang #{$seri}", 'message' => 'Harga per kg sangat rendah ('.number_format($perKg, 4).'/kg) — periksa potensi under-invoicing.'];
                } elseif ($perKg > 100000) {
                    $f[] = ['level' => 'info', 'field' => "barang #{$seri}", 'message' => 'Harga per kg sangat tinggi ('.number_format($perKg, 2).'/kg) — pastikan satuan & nilai benar.'];
                }
            }

            $uraian = trim((string) data_get($item, 'uraian'));
            if (mb_strlen($uraian) < 5) {
                $f[] = ['level' => 'warning', 'field' => "barang #{$seri} uraian", 'message' => 'Uraian barang terlalu singkat/generik.'];
            }
        }

        // Kelengkapan entitas utama.
        if (! $document->partyName()) {
            $f[] = ['level' => 'error', 'field' => 'entitas', 'message' => 'Nama pihak utama (eksportir/importir) belum terisi.'];
        }
        if (! $document->partyNpwp()) {
            $f[] = ['level' => 'warning', 'field' => 'NPWP', 'message' => 'NPWP pihak utama belum terisi.'];
        }

        return $f;
    }

    /**
     * Lapisan analisis AI.
     *
     * @return array{0: string, 1: array<int, array{level: string, field: ?string, message: string}>}
     */
    protected function aiChecks(Document $document): array
    {
        $system = <<<'SYS'
Anda auditor kepabeanan Indonesia (DJBC/CEISA 4.0) yang teliti. Tugas: meninjau satu
dokumen kepabeanan SEBELUM dikirim dan menandai potensi masalah yang bisa menyebabkan
penolakan, jalur merah, atau temuan audit. Fokus: konsistensi kode HS dengan uraian
barang, kewajaran harga/berat, kelengkapan & konsistensi data, dan kepatuhan format.

WAJIB jawab HANYA dengan JSON valid (tanpa teks lain, tanpa markdown), berbentuk:
{"findings":[{"level":"error|warning|info","field":"nama field/lokasi","message":"penjelasan ringkas dalam Bahasa Indonesia"}]}
Jika tidak ada masalah, kembalikan {"findings":[]}. Maksimum 10 temuan paling penting.
SYS;

        $user = "Jenis dokumen: {$document->doc_type}\n\nData dokumen (JSON):\n"
            .json_encode($document->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $result = $this->ai->chat($system, $user);

        return [$result['provider'], $this->parseFindings($result['text'])];
    }

    /**
     * Ekstrak array findings dari teks model (toleran terhadap pembungkus ```json).
     *
     * @return array<int, array{level: string, field: ?string, message: string}>
     */
    protected function parseFindings(string $text): array
    {
        // Ambil blok JSON pertama { ... }.
        if (preg_match('/\{.*\}/s', $text, $m)) {
            $text = $m[0];
        }

        $data = json_decode($text, true);
        $findings = data_get($data, 'findings', []);

        if (! is_array($findings)) {
            return [];
        }

        $out = [];
        foreach ($findings as $item) {
            $message = trim((string) data_get($item, 'message'));
            if ($message === '') {
                continue;
            }
            $level = strtolower((string) data_get($item, 'level', 'info'));
            $out[] = [
                'level' => in_array($level, ['error', 'warning', 'info'], true) ? $level : 'info',
                'field' => data_get($item, 'field') ? (string) data_get($item, 'field') : null,
                'message' => $message,
            ];
        }

        return $out;
    }
}
