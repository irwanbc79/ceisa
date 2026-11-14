<?php

namespace Tests\Feature;

use App\Models\CeisaCredential;
use App\Models\CeisaReference;
use App\Models\Document;
use App\Models\DocumentAuditLog;
use App\Models\User;
use App\Services\DocumentValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CompliancePhaseOneTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCredential(User $user): CeisaCredential
    {
        return CeisaCredential::create([
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
    }

    public function test_validator_flags_npwp_nitku_pattern_mandatory_and_reference_enum(): void
    {
        config(['ai.enabled' => false]);

        CeisaReference::create(['type' => 'valuta', 'code' => 'USD', 'label' => 'US Dollar']);
        CeisaReference::create(['type' => 'kantor_pabean', 'code' => '040300', 'label' => 'Tanjung Priok']);
        CeisaReference::create(['type' => 'satuan', 'code' => 'PCE', 'label' => 'Piece']);

        $user = User::factory()->create(['role' => User::ROLE_OPERATOR]);

        $badDoc = $user->documents()->create([
            'doc_type' => 'BC30',
            'source' => Document::SOURCE_H2H,
            'status' => Document::STATUS_DRAFT,
            'payload' => [
                'header' => [
                    'kode_kantor' => '999999',
                    'valuta' => 'XYZ',
                    'nitku' => '12345',
                    'eksportir' => ['nama' => 'PT M2B', 'npwp' => '1234'],
                ],
                'barang' => [[
                    'seri' => 1,
                    'hs_code' => '84713020',
                    'uraian' => 'Laptop Komputer',
                    'jumlah_satuan' => 5,
                    'kode_satuan' => 'ZZZ',
                    'netto' => 10,
                    'nilai_fob' => 5000,
                ]],
            ],
        ]);

        $result = (new DocumentValidator)->validate($badDoc);
        $messages = collect($result['rule_findings'])->pluck('message')->implode(' | ');

        $this->assertStringContainsString('[CEISA 1023] Format NPWP', $messages);
        $this->assertStringContainsString('[CEISA 1023] Format NITKU', $messages);
        $this->assertStringContainsString("[CEISA 1008] Nilai '999999'", $messages);
        $this->assertStringContainsString("[CEISA 1008] Nilai 'XYZ'", $messages);
        $this->assertStringContainsString("[CEISA 1008] Nilai 'ZZZ'", $messages);

        $goodDoc = $user->documents()->create([
            'doc_type' => 'BC30',
            'source' => Document::SOURCE_H2H,
            'status' => Document::STATUS_DRAFT,
            'payload' => [
                'header' => [
                    'kode_kantor' => '040300',
                    'valuta' => 'USD',
                    'nitku' => '0123456789012345000000',
                    'eksportir' => ['nama' => 'PT M2B', 'npwp' => '0123456789012345'],
                ],
                'barang' => [[
                    'seri' => 1,
                    'hs_code' => '84713020',
                    'uraian' => 'Laptop Komputer',
                    'jumlah_satuan' => 5,
                    'kode_satuan' => 'PCE',
                    'netto' => 10,
                    'nilai_fob' => 5000,
                ]],
            ],
        ]);

        $clean = (new DocumentValidator)->validate($goodDoc);
        $this->assertEmpty($clean['rule_findings']);
    }

    public function test_upload_dokap_and_npd_hits_ceisa_endpoint_and_records_audit_log(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OPERATOR]);
        $this->makeCredential($user);

        $doc = $user->documents()->create([
            'doc_type' => 'BC20',
            'source' => Document::SOURCE_H2H,
            'status' => Document::STATUS_SUBMITTED,
            'nomor_aju' => '000020M2B20261114000001',
            'payload' => [
                'header' => ['importir' => ['nama' => 'PT M2B', 'npwp' => '0123456789012345']],
            ],
        ]);

        Http::fake([
            'https://apis-gw.beacukai.go.id/v2/openapi/file/upload-dokap-npd' => Http::response([
                'status' => 'OK',
                'message' => 'Uploaded',
            ], 200),
        ]);

        $pdf = UploadedFile::fake()->create('respon-npd.pdf', 120, 'application/pdf');

        $response = $this->actingAs($user)
            ->post(route('documents.upload-dokap', $doc), [
                'upload_type' => 'npd',
                'seri' => 1,
                'keterangan' => 'Invoice Revisi',
                'file' => $pdf,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('document_audit_logs', [
            'document_id' => $doc->id,
            'user_id' => $user->id,
            'action' => 'npd_uploaded',
        ]);
    }

    public function test_upload_dokap_requires_nomor_aju_and_validates_mime(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OPERATOR]);
        $this->makeCredential($user);

        $draft = $user->documents()->create([
            'doc_type' => 'BC20',
            'source' => Document::SOURCE_H2H,
            'status' => Document::STATUS_DRAFT,
            'payload' => [],
        ]);

        $pdf = UploadedFile::fake()->create('dokap.pdf', 50, 'application/pdf');

        $this->actingAs($user)
            ->post(route('documents.upload-dokap', $draft), [
                'upload_type' => 'dokap',
                'seri' => 1,
                'file' => $pdf,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $draft->update(['nomor_aju' => '000020M2B20261114000002']);

        $badFile = UploadedFile::fake()->create('malware.exe', 50, 'application/octet-stream');

        $this->actingAs($user)
            ->post(route('documents.upload-dokap', $draft), [
                'upload_type' => 'dokap',
                'seri' => 1,
                'file' => $badFile,
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_document_show_renders_upload_panel_and_audit_trail(): void
    {
        config(['ai.enabled' => false]);
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $doc = $user->documents()->create([
            'doc_type' => 'BC20',
            'source' => Document::SOURCE_H2H,
            'status' => Document::STATUS_SUBMITTED,
            'nomor_aju' => '000020M2B20261114000003',
            'payload' => [
                'header' => ['importir' => ['nama' => 'PT M2B', 'npwp' => '0123456789012345']],
            ],
        ]);

        DocumentAuditLog::record($doc, 'created', 'Dokumen dibuat.', [], $user);

        $this->actingAs($user)
            ->post(route('documents.validate', $doc))
            ->assertRedirect(route('documents.show', $doc));

        $this->assertDatabaseHas('document_audit_logs', [
            'document_id' => $doc->id,
            'action' => 'validated',
        ]);

        $this->actingAs($user)
            ->get(route('documents.show', $doc))
            ->assertOk()
            ->assertSee('Upload Dokap')
            ->assertSee('Audit Trail Aktivitas')
            ->assertSee('Validasi kepatuhan pra-kirim dijalankan.');
    }
}
