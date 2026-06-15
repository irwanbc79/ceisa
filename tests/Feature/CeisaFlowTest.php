<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use App\Services\CeisaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CeisaFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function authedUser(): User
    {
        return User::factory()->create(['role' => User::ROLE_OPERATOR]);
    }

    /**
     * Payload form BC 3.0 ekspor lengkap (struktur CEISA 4.0).
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function bc30Payload(array $overrides = []): array
    {
        return array_merge([
            'doc_type' => 'BC30',
            // Header
            'kantor_muat' => 'IDJKT',
            'jenis_ekspor' => 'Biasa',
            'kategori_ekspor' => 'Umum',
            'cara_dagang' => 'Biasa',
            'cara_bayar' => 'Biasa/Tunai',
            'komoditi' => 'NON_MIGAS',
            'curah' => 'NON_CURAH',
            // Entitas
            'nama_eksportir' => 'PT Mora Multi Berkah',
            'npwp_eksportir' => '012345678901000',
            'alamat_eksportir' => 'Jakarta',
            'nama_penerima' => 'ACME Pte Ltd',
            'negara_tujuan' => 'SG',
            // Pengangkut
            'pelabuhan_muat' => 'IDJKT',
            'pelabuhan_tujuan' => 'SGSIN',
            // Transaksi
            'kode_valuta' => 'USD',
            'ndpbm' => 15800,
            'incoterm' => 'FOB',
            'nilai_fob' => 1500.50,
            'bruto' => 130.0,
            // Pernyataan
            'pernyataan_nama' => 'Irwan',
            'pernyataan_jabatan' => 'Direktur',
            // Barang
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
        ], $overrides);
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
                'username' => 'm2b_user',
                'password' => 'm2b_pass',
                'api_key' => 'secret-key',
                'app_id' => 'APP123',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ceisa_credentials', [
            'user_id' => $user->id,
            'app_id' => 'APP123',
        ]);

        // Kredensial sensitif tersimpan terenkripsi -> tidak boleh plaintext di DB
        $credential = $user->fresh()->ceisaCredential;
        $this->assertNotSame('secret-key', $credential->getRawOriginal('api_key'));
        $this->assertSame('secret-key', $credential->api_key);
        $this->assertNotSame('m2b_pass', $credential->getRawOriginal('password'));
        $this->assertSame('m2b_user', $credential->username);
        $this->assertSame('m2b_pass', $credential->password);
    }

    public function test_login_uses_official_h2h_endpoint_and_headers(): void
    {
        Http::fake([
            '*openapi-auth*' => Http::response(['access_token' => 'TOK', 'expires_in' => 3600], 200),
        ]);

        $user = $this->authedUser();
        $credential = $user->ceisaCredential()->create([
            'username' => 'm2b_user',
            'password' => 'm2b_pass',
            'api_key' => 'KEY-123',
        ]);

        $token = CeisaService::forCredential($credential)->getToken();
        $this->assertSame('TOK', $token);

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), '/v1/openapi-auth/user/login')
                && $request->method() === 'POST'
                && $request['username'] === 'm2b_user'
                && $request['password'] === 'm2b_pass'
                && $request->hasHeader('beacukai-api-key', 'KEY-123');
        });
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
            '*openapi-auth*' => Http::response([
                'access_token' => 'TOKEN-XYZ',
                'expires_in' => 3600,
            ], 200),
            '*/v2/openapi/document*' => Http::response([
                'error_code' => 0,
                'nomor_aju' => '000001-PEB',
                'status' => 'DITERIMA',
            ], 200),
        ]);

        $user = $this->authedUser();
        $user->ceisaCredential()->create([
            'username' => 'm2b_user',
            'password' => 'm2b_pass',
            'app_id' => 'APP123',
            'api_key' => 'secret-key',
        ]);

        $this->actingAs($user)
            ->post('/dokumen/submit', $this->bc30Payload())
            ->assertRedirect();

        $doc = Document::first();
        $this->assertNotNull($doc);
        $this->assertSame('BC30', $doc->doc_type);
        $this->assertSame('000001-PEB', $doc->nomor_aju);
        $this->assertSame(Document::STATUS_SUBMITTED, $doc->status);
        $this->assertSame('TOKEN-XYZ', $user->ceisaCredential->fresh()->token);

        // Struktur payload CEISA 4.0 tersimpan lengkap.
        $this->assertSame('Biasa', data_get($doc->payload, 'header.jenis_ekspor'));
        $this->assertSame('FOB', data_get($doc->payload, 'header.incoterm'));
        $this->assertEquals(15800, data_get($doc->payload, 'header.ndpbm'));
        $this->assertSame('Irwan', data_get($doc->payload, 'header.pernyataan.nama'));
        $this->assertSame('SGSIN', data_get($doc->payload, 'header.pengangkutan.pelabuhan_tujuan'));
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
            '*openapi-auth*' => Http::response([
                'access_token' => 'TOKEN-XYZ',
                'expires_in' => 3600,
            ], 200),
            '*/v2/openapi/document*' => Http::response([
                'error_code' => 0,
                'nomor_aju' => '000002-PIB',
                'status' => 'DITERIMA',
            ], 200),
        ]);

        $user = $this->authedUser();
        $user->ceisaCredential()->create([
            'username' => 'm2b_user',
            'password' => 'm2b_pass',
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
            'username' => 'm2b_user',
            'password' => 'm2b_pass',
            'app_id' => 'APP123',
            'api_key' => 'secret-key',
        ]);

        $this->actingAs($user)
            ->post('/dokumen/submit', $this->bc30Payload(['submit_action' => 'draft']))
            ->assertRedirect();

        $doc = Document::latest('id')->first();
        $this->assertNotNull($doc);
        $this->assertSame('BC30', $doc->doc_type);
        $this->assertSame(Document::STATUS_DRAFT, $doc->status);
        $this->assertNull($doc->nomor_aju);
    }
}
