<?php

namespace Tests\Feature;

use App\Models\CeisaCredential;
use App\Models\CeisaReference;
use App\Models\Document;
use App\Models\PkbSubmission;
use App\Models\User;
use App\Services\DocumentValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CompliancePhaseTwoTest extends TestCase
{
    use RefreshDatabase;

    public function test_validator_checks_bruto_netto_ndpbm_bc20_and_bc30_references(): void
    {
        config(['ai.enabled' => false]);

        CeisaReference::firstOrCreate(['type' => 'jenis_ekspor', 'code' => '1'], ['label' => 'Biasa']);
        CeisaReference::firstOrCreate(['type' => 'cara_dagang', 'code' => '1'], ['label' => 'Biasa']);

        $user = User::factory()->create(['role' => User::ROLE_OPERATOR]);

        $bc20 = $user->documents()->create([
            'doc_type' => 'BC20',
            'source' => Document::SOURCE_H2H,
            'status' => Document::STATUS_DRAFT,
            'payload' => [
                'header' => [
                    'kode_kantor' => '040300',
                    'valuta' => 'USD',
                    'ndpbm' => 0,
                    'bruto' => 5,
                    'importir' => ['nama' => 'PT M2B', 'npwp' => '0123456789012345'],
                ],
                'barang' => [[
                    'seri' => 1,
                    'hs_code' => '84713020',
                    'uraian' => 'Laptop Komputer',
                    'jumlah_satuan' => 2,
                    'kode_satuan' => 'PCE',
                    'netto' => 10,
                    'nilai_cif' => 2000,
                ]],
            ],
        ]);

        $res20 = (new DocumentValidator)->validate($bc20);
        $msg20 = collect($res20['rule_findings'])->pluck('message')->implode(' | ');

        $this->assertStringContainsString('melebihi berat kotor/bruto', $msg20);
        $this->assertStringContainsString('PMK 144/PMK.04/2022', $msg20);
        $this->assertStringContainsString('Negara asal barang wajib diisi', $msg20);
        $this->assertStringContainsString('Kode jenis kemasan per barang wajib diisi', $msg20);

        $bc30 = $user->documents()->create([
            'doc_type' => 'BC30',
            'source' => Document::SOURCE_H2H,
            'status' => Document::STATUS_DRAFT,
            'payload' => [
                'header' => [
                    'kode_kantor' => '040300',
                    'valuta' => 'USD',
                    'kode_jenis_ekspor' => '99',
                    'kode_cara_dagang' => '88',
                    'eksportir' => ['nama' => 'PT M2B', 'npwp' => '0123456789012345'],
                ],
                'barang' => [[
                    'seri' => 1,
                    'hs_code' => '84713020',
                    'uraian' => 'Laptop Komputer',
                    'jumlah_satuan' => 2,
                    'kode_satuan' => 'PCE',
                    'netto' => 10,
                    'nilai_fob' => 2000,
                ]],
            ],
        ]);

        $res30 = (new DocumentValidator)->validate($bc30);
        $msg30 = collect($res30['rule_findings'])->pluck('message')->implode(' | ');

        $this->assertStringContainsString("[CEISA 1008] Nilai '99'", $msg30);
        $this->assertStringContainsString("[CEISA 1008] Nilai '88'", $msg30);
    }

    public function test_pkb_online_records_submission_uploads_proof_and_renders_for_jalur_merah(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OPERATOR]);

        CeisaCredential::create([
            'user_id' => $user->id,
            'env' => 'dev',
            'base_url' => 'https://apis-gw.beacukai.go.id',
            'username' => 'tester',
            'password' => 'secret',
            'api_key' => 'test-key',
            'npwp' => '0123456789012345',
            'token' => 'cached-token',
            'token_expires_at' => now()->addHour(),
        ]);

        $doc = $user->documents()->create([
            'doc_type' => 'BC20',
            'source' => Document::SOURCE_H2H,
            'status' => Document::STATUS_SUBMITTED,
            'jalur' => 'M',
            'nomor_aju' => '000020M2B20261114000099',
            'payload' => [
                'header' => ['importir' => ['nama' => 'PT M2B', 'npwp' => '0123456789012345']],
            ],
        ]);

        Http::fake([
            'https://apis-gw.beacukai.go.id/v2/openapi/file/dokumen' => Http::response([
                'status' => 'OK',
            ], 200),
        ]);

        $pdf = UploadedFile::fake()->create('bukti-barang-siap.pdf', 100, 'application/pdf');

        $this->actingAs($user)
            ->post(route('documents.pkb.store', $doc), [
                'tanggal_periksa' => '2026-11-15',
                'alamat_lokasi' => 'Jl. Raya Pelabuhan Tj Priok No. 10',
                'kode_gudang' => 'JICT1',
                'nama_pic' => 'Budi Santoso',
                'telepon_pic' => '081234567890',
                'bukti_siap' => $pdf,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pkb_submissions', [
            'document_id' => $doc->id,
            'kode_gudang' => 'JICT1',
            'status' => PkbSubmission::STATUS_SUBMITTED,
        ]);

        $this->assertDatabaseHas('document_audit_logs', [
            'document_id' => $doc->id,
            'action' => 'pkb_recorded',
        ]);

        $this->actingAs($user)
            ->get(route('documents.show', $doc))
            ->assertOk()
            ->assertSee('PKB Online (Jalur Merah)')
            ->assertSee('Gudang JICT1');
    }
}
