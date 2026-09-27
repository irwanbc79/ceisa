<?php

namespace Tests\Feature;

use App\Jobs\SubmitCeisaDocumentJob;
use App\Jobs\VerifyCeisaStatusJob;
use App\Models\Document;
use App\Models\User;
use App\Models\WebhookLog;
use App\Services\CeisaStatusMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AsyncJobsAndFsmTest extends TestCase
{
    use RefreshDatabase;

    protected function authedUser(): User
    {
        return User::factory()->create(['role' => User::ROLE_OPERATOR]);
    }

    protected function bc30Payload(array $overrides = []): array
    {
        return array_merge([
            'doc_type' => 'BC30',
            'kantor_muat' => '010700',
            'kantor_pendaftaran' => '010700',
            'kantor_ekspor' => '010700',
            'jenis_ekspor' => '1',
            'kategori_ekspor' => '10',
            'cara_dagang' => '1',
            'cara_bayar' => '1',
            'jenis_pengangkutan' => '1',
            'kode_lokasi' => '2',
            'tanggal_periksa' => '2026-07-21',
            'komoditi' => 'NON_MIGAS',
            'curah' => 'NON_CURAH',
            'nama_eksportir' => 'PT Mora Multi Berkah',
            'npwp_eksportir' => '012345678901000',
            'alamat_eksportir' => 'Jakarta',
            'nama_penerima' => 'ACME Pte Ltd',
            'alamat_penerima' => 'Singapore',
            'negara_tujuan' => 'SG',
            'cara_angkut' => '1',
            'kode_bendera' => 'ID',
            'nama_sarana' => 'MV TEST',
            'voy_flight' => 'V001',
            'pelabuhan_muat' => 'IDJKT',
            'pelabuhan_ekspor' => 'IDJKT',
            'pelabuhan_tujuan' => 'SGSIN',
            'tanggal_ekspor' => '2026-07-22',
            'kode_valuta' => 'USD',
            'ndpbm' => 15800,
            'incoterm' => 'FOB',
            'nilai_fob' => 1500.50,
            'asuransi_jenis' => 'DN',
            'nilai_asuransi' => 0,
            'freight' => 0,
            'bruto' => 130.0,
            'pernyataan_nama' => 'Irwan',
            'pernyataan_jabatan' => 'Direktur',
            'pernyataan_kota' => 'Medan',
            'dokumen' => [
                [
                    'kode_dokumen' => '380',
                    'nomor_dokumen' => 'INV-001',
                    'tanggal_dokumen' => '2026-07-20',
                ],
            ],
            'barang' => [
                [
                    'hs_code' => '6109100000',
                    'uraian' => 'Kaos katun',
                    'jumlah_satuan' => 100,
                    'kode_satuan' => 'PCE',
                    'netto' => 25.5,
                    'nilai_fob' => 1500.50,
                    'jumlah_kemasan' => 1,
                    'kode_kemasan' => 'CT',
                    'merk_kemasan' => 'M2B',
                ],
            ],
        ], $overrides);
    }

    public function test_submit_ceisa_document_job_processes_successfully(): void
    {
        Http::fake([
            '*user/login*' => Http::response([
                'access_token' => 'TOKEN-ASYNC-123',
                'expires_in' => 3600,
            ], 200),
            '*/openapi/document*' => Http::response([
                'error_code' => 0,
                'nomor_aju' => '000001-ASYNC',
                'status' => 'DITERIMA',
            ], 200),
        ]);

        $user = $this->authedUser();
        $user->ceisaCredential()->create([
            'username' => 'm2b_async',
            'password' => 'm2b_pass',
            'npwp' => '012345678901000',
            'app_id' => 'APP-ASYNC',
            'api_key' => 'secret-async',
        ]);

        $this->actingAs($user)->post('/dokumen/submit', $this->bc30Payload(['submit_action' => 'draft']));
        $document = Document::firstOrFail();

        SubmitCeisaDocumentJob::dispatch($document);

        $document->refresh();
        $this->assertSame(Document::STATUS_SUBMITTED, $document->status);
    }

    public function test_fsm_prevents_demoting_accepted_status(): void
    {
        $user = $this->authedUser();
        $document = $user->documents()->create([
            'doc_type' => 'BC30',
            'status' => Document::STATUS_ACCEPTED,
            'nomor_aju' => '000002-FSM',
            'payload' => [],
        ]);

        // FSM rule: canTransitionTo rejects demoting ACCEPTED to SUBMITTED or DRAFT
        $this->assertFalse($document->canTransitionTo(Document::STATUS_SUBMITTED));
        $this->assertFalse($document->canTransitionTo(Document::STATUS_SUBMITTING));
        $this->assertFalse($document->canTransitionTo(Document::STATUS_DRAFT));

        // CeisaStatusMapper::mapStatus preserves ACCEPTED even if stale payload says PROSES
        $stalePayload = [
            'status' => 'PROSES PEMERIKSAAN',
        ];

        $mappedStatus = CeisaStatusMapper::mapStatus($stalePayload, $document);
        $this->assertSame(Document::STATUS_ACCEPTED, $mappedStatus);
    }

    public function test_verify_ceisa_status_job_queries_official_gateway(): void
    {
        Http::fake([
            '*user/login*' => Http::response([
                'access_token' => 'TOKEN-VERIFY-999',
                'expires_in' => 3600,
            ], 200),
            '*/openapi/status*' => Http::response([
                'dataStatus' => [
                    ['nomorAju' => '000003-VERIFY', 'status' => 'SPPB', 'waktuStatus' => '2026-09-27 10:00:00'],
                ],
                'dataRespon' => [
                    ['nomorAju' => '000003-VERIFY', 'kodeRespon' => '3015', 'keterangan' => 'SURAT PERSETUJUAN PENGELUARAN BARANG (SPPB)'],
                ],
            ], 200),
        ]);

        $user = $this->authedUser();
        $user->ceisaCredential()->create([
            'username' => 'm2b_verify',
            'password' => 'm2b_pass',
            'npwp' => '012345678901000',
            'api_key' => 'secret-verify',
        ]);

        $document = $user->documents()->create([
            'doc_type' => 'BC20',
            'status' => Document::STATUS_SUBMITTED,
            'nomor_aju' => '000003-VERIFY',
            'payload' => [],
        ]);

        $log = WebhookLog::create([
            'fingerprint' => 'test-fingerprint-123',
            'event' => 'Respon',
            'nomor_aju' => '000003-VERIFY',
            'payload' => ['status' => 'SPPB'],
            'verified' => false,
            'received_at' => now(),
        ]);

        $job = new VerifyCeisaStatusJob($document, ['status' => 'SPPB'], $log->id);
        $job->handle();

        $document->refresh();
        $this->assertSame(Document::STATUS_ACCEPTED, $document->status);

        $log->refresh();
        $this->assertTrue($log->verified);
        $this->assertTrue($log->processed);
    }
}

