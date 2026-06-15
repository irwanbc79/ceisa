<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CeisaFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function authedUser(): User
    {
        return User::factory()->create(['role' => User::ROLE_OPERATOR]);
    }

    public function test_dashboard_loads(): void
    {
        $this->actingAs($this->authedUser())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Dashboard');
    }

    public function test_user_can_save_ceisa_credential(): void
    {
        $user = $this->authedUser();

        $this->actingAs($user)
            ->post('/settings/ceisa', [
                'app_id' => 'APP123',
                'api_key' => 'secret-key',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ceisa_credentials', [
            'user_id' => $user->id,
            'app_id' => 'APP123',
        ]);

        // api_key tersimpan terenkripsi -> tidak boleh plaintext di DB
        $credential = $user->fresh()->ceisaCredential;
        $this->assertNotSame('secret-key', $credential->getRawOriginal('api_key'));
        $this->assertSame('secret-key', $credential->api_key);
    }

    public function test_create_form_requires_credential(): void
    {
        $this->actingAs($this->authedUser())
            ->get('/dokumen/buat')
            ->assertRedirect(route('settings.ceisa.edit'));
    }

    public function test_submit_document_sends_to_ceisa_and_persists(): void
    {
        Http::fake([
            '*/openapi/auth*' => Http::response([
                'access_token' => 'TOKEN-XYZ',
                'expires_in' => 3600,
            ], 200),
            '*/openapi/document*' => Http::response([
                'error_code' => 0,
                'nomor_aju' => '000001-PEB',
                'status' => 'DITERIMA',
            ], 200),
        ]);

        $user = $this->authedUser();
        $user->ceisaCredential()->create([
            'app_id' => 'APP123',
            'api_key' => 'secret-key',
        ]);

        $payload = [
            'doc_type' => 'BC30',
            'nama_eksportir' => 'PT Mora Multi Berkah',
            'npwp_eksportir' => '01.234.567.8-901.000',
            'alamat_eksportir' => 'Jakarta',
            'nama_penerima' => 'ACME Pte Ltd',
            'negara_tujuan' => 'SG',
            'pelabuhan_muat' => 'IDJKT',
            'kode_valuta' => 'USD',
            'nilai_fob' => 1500.50,
            'barang' => [
                [
                    'hs_code' => '6109100000',
                    'uraian' => 'Kaos katun',
                    'jumlah_satuan' => 100,
                    'kode_satuan' => 'PCE',
                    'netto' => 25.5,
                    'nilai_fob' => 1500.50,
                ],
            ],
        ];

        $this->actingAs($user)
            ->post('/dokumen/submit', $payload)
            ->assertRedirect();

        $doc = Document::first();
        $this->assertNotNull($doc);
        $this->assertSame('BC30', $doc->doc_type);
        $this->assertSame('000001-PEB', $doc->nomor_aju);
        $this->assertSame(Document::STATUS_SUBMITTED, $doc->status);
        $this->assertSame('TOKEN-XYZ', $user->ceisaCredential->fresh()->token);
    }

    public function test_webhook_updates_document_status(): void
    {
        $user = $this->authedUser();
        $doc = $user->documents()->create([
            'doc_type' => 'BC30',
            'nomor_aju' => '000001-PEB',
            'payload' => ['x' => 1],
            'status' => Document::STATUS_SUBMITTED,
        ]);

        $this->postJson('/api/webhook/ceisa', [
            'nomor_aju' => '000001-PEB',
            'nomor_daftar' => 'REG-999',
            'status' => 'DITERIMA / SPPB',
        ])->assertOk();

        $doc->refresh();
        $this->assertSame(Document::STATUS_ACCEPTED, $doc->status);
        $this->assertSame('REG-999', $doc->nomor_daftar);
        $this->assertDatabaseHas('webhook_logs', ['document_id' => $doc->id, 'processed' => true]);
    }

    public function test_webhook_without_identifier_does_not_mutate_documents(): void
    {
        $user = $this->authedUser();
        $doc = $user->documents()->create([
            'doc_type' => 'BC30',
            'nomor_aju' => '000001-PEB',
            'payload' => ['x' => 1],
            'status' => Document::STATUS_SUBMITTED,
        ]);

        // Payload tanpa nomor_aju & nomor_daftar tidak boleh mencocokkan dokumen acak.
        $this->postJson('/api/webhook/ceisa', [
            'status' => 'DITERIMA / SPPB',
        ])->assertOk();

        $doc->refresh();
        $this->assertSame(Document::STATUS_SUBMITTED, $doc->status);
        $this->assertDatabaseHas('webhook_logs', ['document_id' => null, 'processed' => false]);
    }

    public function test_submit_bc20_document_sends_to_ceisa_and_persists(): void
    {
        Http::fake([
            '*/openapi/auth*' => Http::response([
                'access_token' => 'TOKEN-XYZ',
                'expires_in' => 3600,
            ], 200),
            '*/openapi/document*' => Http::response([
                'error_code' => 0,
                'nomor_aju' => '000002-PIB',
                'status' => 'DITERIMA',
            ], 200),
        ]);

        $user = $this->authedUser();
        $user->ceisaCredential()->create([
            'app_id' => 'APP123',
            'api_key' => 'secret-key',
        ]);

        $payload = [
            'doc_type' => 'BC20',
            'nama_importir' => 'PT Mora Multi Berkah',
            'npwp_importir' => '01.234.567.8-901.000',
            'alamat_importir' => 'Jakarta',
            'nama_pemasok' => 'Tokyo Machinery',
            'negara_pemasok' => 'JP',
            'pelabuhan_muat' => 'JPTYO',
            'pelabuhan_bongkar' => 'IDTPP',
            'kode_valuta' => 'JPY',
            'nilai_cif' => 180000.00,
            'barang' => [
                [
                    'hs_code' => '8471302000',
                    'uraian' => 'Laptop Office',
                    'jumlah_satuan' => 10,
                    'kode_satuan' => 'UNT',
                    'netto' => 25.0,
                    'nilai_cif' => 180000.00,
                ],
            ],
        ];

        $this->actingAs($user)
            ->post('/dokumen/submit', $payload)
            ->assertRedirect();

        $doc = Document::latest('id')->first();
        $this->assertNotNull($doc);
        $this->assertSame('BC20', $doc->doc_type);
        $this->assertSame('000002-PIB', $doc->nomor_aju);
        $this->assertSame(Document::STATUS_SUBMITTED, $doc->status);
    }

    public function test_save_document_as_draft_does_not_submit_to_ceisa(): void
    {
        $user = $this->authedUser();
        $user->ceisaCredential()->create([
            'app_id' => 'APP123',
            'api_key' => 'secret-key',
        ]);

        $payload = [
            'doc_type' => 'BC30',
            'submit_action' => 'draft',
            'nama_eksportir' => 'PT Mora Multi Berkah',
            'npwp_eksportir' => '01.234.567.8-901.000',
            'alamat_eksportir' => 'Jakarta',
            'nama_penerima' => 'ACME Pte Ltd',
            'negara_tujuan' => 'SG',
            'pelabuhan_muat' => 'IDJKT',
            'kode_valuta' => 'USD',
            'nilai_fob' => 1500.50,
            'barang' => [
                [
                    'hs_code' => '6109100000',
                    'uraian' => 'Kaos katun',
                    'jumlah_satuan' => 100,
                    'kode_satuan' => 'PCE',
                    'netto' => 25.5,
                    'nilai_fob' => 1500.50,
                ],
            ],
        ];

        $this->actingAs($user)
            ->post('/dokumen/submit', $payload)
            ->assertRedirect();

        $doc = Document::latest('id')->first();
        $this->assertNotNull($doc);
        $this->assertSame('BC30', $doc->doc_type);
        $this->assertSame(Document::STATUS_DRAFT, $doc->status);
        $this->assertNull($doc->nomor_aju);
    }
}
