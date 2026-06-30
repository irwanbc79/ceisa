<?php

namespace Tests\Feature;

use App\Models\CeisaReference;
use App\Models\Document;
use App\Models\User;
use App\Models\WebhookLog;
use App\Services\CeisaPayloadBuilder;
use App\Services\CeisaService;
use Database\Seeders\CeisaReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
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
                'id_platform' => 'PLAT-001',
                'app_id' => 'APP123',
                'environment' => 'production',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ceisa_credentials', [
            'user_id' => $user->id,
            'app_id' => 'APP123',
            'base_url' => 'https://apis-gw.beacukai.go.id',
        ]);

        // Kredensial sensitif tersimpan terenkripsi -> tidak boleh plaintext di DB
        $credential = $user->fresh()->ceisaCredential;
        $this->assertNotSame('secret-key', $credential->getRawOriginal('api_key'));
        $this->assertSame('secret-key', $credential->api_key);
        $this->assertNotSame('m2b_pass', $credential->getRawOriginal('password'));
        $this->assertSame('m2b_user', $credential->username);
        $this->assertSame('m2b_pass', $credential->password);
        $this->assertSame('PLAT-001', $credential->id_platform);
    }

    public function test_login_sends_id_platform_header(): void
    {
        Http::fake([
            '*user/login*' => Http::response(['access_token' => 'TOK', 'expires_in' => 3600], 200),
        ]);

        $user = $this->authedUser();
        $credential = $user->ceisaCredential()->create([
            'username' => 'm2b_user',
            'password' => 'm2b_pass',
            'api_key' => 'KEY-123',
            'id_platform' => 'PLAT-XYZ',
        ]);

        CeisaService::forCredential($credential)->getToken();

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), '/v1/openapi-auth/user/login')
                && $request->hasHeader('Beacukai-Api-Key', 'KEY-123')
                && $request->hasHeader('id_platform', 'PLAT-XYZ');
        });
    }

    public function test_user_can_save_ceisa_credential_with_sandbox(): void
    {
        $user = $this->authedUser();

        $this->actingAs($user)
            ->post('/settings/ceisa', [
                'username' => 'm2b_user',
                'password' => 'm2b_pass',
                'api_key' => 'secret-key',
                'app_id' => 'APP123',
                'environment' => 'sandbox',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ceisa_credentials', [
            'user_id' => $user->id,
            'app_id' => 'APP123',
            'base_url' => 'https://apisdev-gw.beacukai.go.id',
        ]);
    }

    public function test_user_can_save_ceisa_credential_with_custom_url(): void
    {
        $user = $this->authedUser();

        $this->actingAs($user)
            ->post('/settings/ceisa', [
                'username' => 'm2b_user',
                'password' => 'm2b_pass',
                'api_key' => 'secret-key',
                'app_id' => 'APP123',
                'environment' => 'custom',
                'custom_base_url' => 'https://custom-gateway.example.com/',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ceisa_credentials', [
            'user_id' => $user->id,
            'app_id' => 'APP123',
            'base_url' => 'https://custom-gateway.example.com',
        ]);
    }

    public function test_login_uses_official_h2h_endpoint_and_headers(): void
    {
        Http::fake([
            '*user/login*' => Http::response(['access_token' => 'TOK', 'expires_in' => 3600], 200),
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
                && $request->hasHeader('Beacukai-Api-Key', 'KEY-123');
        });
    }

    public function test_login_uses_credential_base_url_when_provided(): void
    {
        Http::fake([
            'https://custom-gateway.example.com/*' => Http::response(['access_token' => 'TOK-CUSTOM', 'expires_in' => 3600], 200),
        ]);

        $user = $this->authedUser();
        $credential = $user->ceisaCredential()->create([
            'username' => 'm2b_user',
            'password' => 'm2b_pass',
            'api_key' => 'KEY-123',
            'base_url' => 'https://custom-gateway.example.com',
        ]);

        $token = CeisaService::forCredential($credential)->getToken();
        $this->assertSame('TOK-CUSTOM', $token);

        Http::assertSent(function (Request $request) {
            return str_starts_with($request->url(), 'https://custom-gateway.example.com/v1/openapi-auth/user/login');
        });
    }

    public function test_login_stores_refresh_token_from_response(): void
    {
        Http::fake([
            '*user/login*' => Http::response([
                'access_token' => 'ACCESS-1',
                'refresh_token' => 'REFRESH-1',
                'expires_in' => 300,
            ], 200),
        ]);

        $user = $this->authedUser();
        $credential = $user->ceisaCredential()->create([
            'username' => 'm2b_user',
            'password' => 'm2b_pass',
            'api_key' => 'KEY-123',
        ]);

        CeisaService::forCredential($credential)->getToken();

        $credential->refresh();
        $this->assertSame('ACCESS-1', $credential->token);
        $this->assertSame('REFRESH-1', $credential->refresh_token);
    }

    public function test_expired_token_is_refreshed_via_update_token_endpoint(): void
    {
        Http::fake([
            '*user/update-token*' => Http::response(['access_token' => 'ACCESS-2'], 200),
            '*user/login*' => Http::response(['access_token' => 'SHOULD-NOT-BE-USED'], 200),
        ]);

        $user = $this->authedUser();
        $credential = $user->ceisaCredential()->create([
            'username' => 'm2b_user',
            'password' => 'm2b_pass',
            'api_key' => 'KEY-123',
            'token' => 'OLD-EXPIRED',
            'refresh_token' => 'REFRESH-1',
            'token_expires_at' => now()->subMinutes(5),
        ]);

        $token = CeisaService::forCredential($credential)->refreshTokenIfExpired();

        $this->assertSame('ACCESS-2', $token);

        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/v1/openapi-auth/user/update-token')
            && $r->method() === 'POST'
            && $r->hasHeader('Authorization', 'REFRESH-1'));
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), 'user/login'));
    }

    public function test_refresh_falls_back_to_full_login_when_update_token_fails(): void
    {
        Http::fake([
            '*user/update-token*' => Http::response(['message' => 'refresh expired'], 401),
            '*user/login*' => Http::response(['access_token' => 'ACCESS-FRESH', 'refresh_token' => 'REFRESH-NEW', 'expires_in' => 300], 200),
        ]);

        $user = $this->authedUser();
        $credential = $user->ceisaCredential()->create([
            'username' => 'm2b_user',
            'password' => 'm2b_pass',
            'api_key' => 'KEY-123',
            'token' => 'OLD-EXPIRED',
            'refresh_token' => 'REFRESH-OLD',
            'token_expires_at' => now()->subMinutes(5),
        ]);

        $token = CeisaService::forCredential($credential)->refreshTokenIfExpired();

        $this->assertSame('ACCESS-FRESH', $token);
        $credential->refresh();
        $this->assertSame('REFRESH-NEW', $credential->refresh_token);
    }

    public function test_create_form_requires_credential(): void
    {
        $this->actingAs($this->authedUser())
            ->get('/dokumen/buat')
            ->assertRedirect(route('settings.ceisa.edit'));
    }

    public function test_create_form_loads_reference_data_from_db(): void
    {
        $this->seed(CeisaReferenceSeeder::class);

        $user = $this->authedUser();
        $user->ceisaCredential()->create([
            'username' => 'm2b_user',
            'password' => 'm2b_pass',
            'api_key' => 'secret-key',
        ]);

        // Wizard memuat referensi server (negara, kantor pabean, pelabuhan) dari DB.
        $this->actingAs($user)
            ->get('/dokumen/buat')
            ->assertOk()
            ->assertSee('IN - India')
            ->assertSee('011200 - KPPBC TMP C Kuala Tanjung')
            ->assertSee('IDKTJ - Kuala Tanjung, Sumut');
    }

    public function test_submit_document_sends_to_ceisa_and_persists(): void
    {
        Http::fake([
            '*user/login*' => Http::response([
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

    public function test_submit_document_with_supporting_documents_and_containers(): void
    {
        Http::fake([
            '*user/login*' => Http::response([
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
            'username' => 'm2b_user',
            'password' => 'm2b_pass',
            'app_id' => 'APP123',
            'api_key' => 'secret-key',
        ]);

        $payload = $this->bc30Payload([
            'dokumen' => [
                [
                    'kode_dokumen' => '380',
                    'nomor_dokumen' => 'INV-2026-001',
                    'tanggal_dokumen' => '2026-06-30',
                ],
            ],
            'kontainer' => [
                [
                    'nomor_kontainer' => 'MSKU1234567',
                    'kode_ukuran' => '40',
                    'kode_tipe' => '8',
                    'kode_status' => 'FCL',
                ],
            ],
        ]);

        $this->actingAs($user)
            ->post('/dokumen/submit', $payload)
            ->assertRedirect();

        $doc = Document::first();
        $this->assertNotNull($doc);
        
        // Check database storage
        $this->assertCount(1, data_get($doc->payload, 'dokumen'));
        $this->assertSame('INV-2026-001', data_get($doc->payload, 'dokumen.0.nomor_dokumen'));
        
        $this->assertCount(1, data_get($doc->payload, 'kontainer'));
        $this->assertSame('MSKU1234567', data_get($doc->payload, 'kontainer.0.nomor_kontainer'));

        // Check H2H payload building mapping
        $builderPayload = (new CeisaPayloadBuilder())->build('BC30', $doc->payload, '000001-PEB');
        
        $this->assertCount(1, data_get($builderPayload, 'dokumen'));
        $this->assertSame('380', data_get($builderPayload, 'dokumen.0.kodeDokumen'));
        $this->assertSame('INV-2026-001', data_get($builderPayload, 'dokumen.0.nomorDokumen'));

        $this->assertCount(1, data_get($builderPayload, 'kontainer'));
        $this->assertSame('MSKU1234567', data_get($builderPayload, 'kontainer.0.nomorKontainer'));
        $this->assertSame('40', data_get($builderPayload, 'kontainer.0.kodeUkuranKontainer'));
        $this->assertSame('8', data_get($builderPayload, 'kontainer.0.kodeTipeKontainer'));
        $this->assertSame('FCL', data_get($builderPayload, 'kontainer.0.kodeStatusKontainer'));
    }

    public function test_submit_retries_once_after_401_with_fresh_login(): void
    {
        Http::fake([
            '*user/login*' => Http::response(['access_token' => 'TOK', 'expires_in' => 3600], 200),
            '*/openapi/document*' => Http::sequence()
                ->push(['message' => 'unauthorized'], 401)
                ->push(['status' => 'OK', 'idHeader' => 'uuid-retry'], 200),
        ]);

        $credential = $this->authedUser()->ceisaCredential()->create([
            'username' => 'm2b_user',
            'password' => 'm2b_pass',
            'api_key' => 'secret-key',
        ]);

        $data = CeisaService::forCredential($credential)
            ->submitDocument('BC30', ['nomorAju' => 'X'], ['is_final' => false]);

        // 401 pertama memicu login ulang + retry → akhirnya sukses.
        $this->assertSame('OK', $data['status']);
        // login awal + submit(401) + login ulang + submit(200) = 4 request.
        Http::assertSentCount(4);
    }

    public function test_submit_document_with_custom_nomor_aju_saves_it(): void
    {
        Http::fake([
            '*user/login*' => Http::response([
                'access_token' => 'TOKEN-XYZ',
                'expires_in' => 3600,
            ], 200),
            '*/openapi/document*' => Http::response([
                'error_code' => 0,
                'nomor_aju' => '04010020260617012345678912',
                'status' => 'DITERIMA',
            ], 200),
        ]);

        $user = $this->authedUser();
        $user->ceisaCredential()->create([
            'username' => 'm2b_user',
            'password' => 'm2b_pass',
            'api_key' => 'secret-key',
        ]);

        $payload = $this->bc30Payload();
        $payload['nomor_aju'] = '04010020260617012345678912';

        $this->actingAs($user)
            ->post('/dokumen/submit', $payload)
            ->assertRedirect();

        $doc = Document::first();
        $this->assertNotNull($doc);
        $this->assertSame('04010020260617012345678912', $doc->nomor_aju);
    }

    public function test_submit_revision_sends_is_revision_query_to_ceisa(): void
    {
        Http::fake([
            '*user/login*' => Http::response([
                'access_token' => 'TOKEN-XYZ',
                'expires_in' => 3600,
            ], 200),
            '*/openapi/document*' => Http::response([
                'status' => 'OK',
                'nomor_aju' => '04010020260617012345678912',
            ], 200),
        ]);

        $user = $this->authedUser();
        $user->ceisaCredential()->create([
            'username' => 'm2b_user',
            'password' => 'm2b_pass',
            'api_key' => 'KEY-123',
        ]);

        $doc = $user->documents()->create([
            'doc_type' => 'BC30',
            'nomor_aju' => '04010020260617012345678912',
            'source' => Document::SOURCE_H2H,
            'payload' => $this->bc30Payload(),
            'status' => Document::STATUS_ACCEPTED,
        ]);

        $this->actingAs($user)
            ->post("/dokumen/{$doc->id}/kirim-pembetulan")
            ->assertRedirect();

        $this->assertSame(Document::STATUS_SUBMITTED, $doc->fresh()->status);

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), 'isRevision=true');
        });
    }

    public function test_submit_sends_is_final_query_and_persists_id_header(): void
    {
        Http::fake([
            '*user/login*' => Http::response([
                'access_token' => 'TOKEN-XYZ',
                'expires_in' => 3600,
            ], 200),
            '*/openapi/document*' => Http::response([
                'status' => 'OK',
                'message' => 'Sukses, Data Berhasil Ditambahkan',
                'idHeader' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
            ], 200),
        ]);

        $user = $this->authedUser();
        $user->ceisaCredential()->create([
            'username' => 'm2b_user',
            'password' => 'm2b_pass',
            'api_key' => 'KEY-123',
        ]);

        $this->actingAs($user)
            ->post('/dokumen/submit', $this->bc30Payload())
            ->assertRedirect();

        $doc = Document::first();
        $this->assertSame('a1b2c3d4-e5f6-7890-abcd-ef1234567890', $doc->id_header);
        $this->assertSame(Document::STATUS_SUBMITTED, $doc->status);

        // Submit sungguhan WAJIB membawa isFinal=true + header standar H2H.
        Http::assertSent(function (Request $request) {
            if (! str_contains($request->url(), '/openapi/document')) {
                return false;
            }

            return str_contains($request->url(), 'isFinal=true')
                && str_contains($request->url(), 'isRevision=false')
                && $request->hasHeader('Beacukai-Api-Key', 'KEY-123')
                && $request->hasHeader('nle-api-key', 'KEY-123')
                && $request->hasHeader('Origin');
        });
    }

    public function test_user_can_archive_old_document(): void
    {
        $user = $this->authedUser();

        $this->actingAs($user)
            ->post('/dokumen/arsip', [
                'doc_type' => 'BC30',
                'nomor_aju' => '301012B628EF20260611000001',
                'status' => 'accepted',
                'jalur' => 'H',
                'nama_perusahaan' => 'PT Sumatera Fan Jaya',
                'tanggal_dokumen' => '2026-06-11',
                'kode_valuta' => 'USD',
                'nilai' => 21250,
            ])
            ->assertRedirect();

        $doc = Document::first();
        $this->assertNotNull($doc);
        $this->assertSame(Document::SOURCE_ARSIP, $doc->source);
        $this->assertTrue($doc->isArchived());
        $this->assertSame(Document::STATUS_ACCEPTED, $doc->status);
        $this->assertSame('H', $doc->jalur);
        $this->assertSame('PT Sumatera Fan Jaya', data_get($doc->payload, 'nama_perusahaan'));
        // Dokumen arsip tampil di Dashboard (riwayat).
        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('301012B628EF20260611000001');
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

    public function test_webhook_respon_sets_status_and_jalur(): void
    {
        $user = $this->authedUser();
        $doc = $user->documents()->create([
            'doc_type' => 'BC30',
            'nomor_aju' => '000010-PEB',
            'payload' => ['x' => 1],
            'status' => Document::STATUS_SUBMITTED,
        ]);

        $this->postJson('/api/webhook/ceisa', [
            'nomor_aju' => '000010-PEB',
            'status' => 'DITERIMA / SPPB',
            'jalur' => 'HIJAU',
        ])->assertOk();

        $doc->refresh();
        $this->assertSame(Document::STATUS_ACCEPTED, $doc->status);
        $this->assertSame(Document::JALUR_HIJAU, $doc->jalur);
        $this->assertDatabaseHas('webhook_logs', ['notification_type' => 'Respon']);
    }

    public function test_webhook_informasi_does_not_change_document(): void
    {
        $user = $this->authedUser();
        $doc = $user->documents()->create([
            'doc_type' => 'BC30',
            'nomor_aju' => '000011-PEB',
            'payload' => ['x' => 1],
            'status' => Document::STATUS_SUBMITTED,
        ]);

        // Notifikasi Informasi tidak boleh mengubah status/jalur dokumen.
        $this->postJson('/api/webhook/ceisa', [
            'jenis' => 'Informasi',
            'nomor_aju' => '000011-PEB',
            'pesan' => 'Pengumuman pemeliharaan sistem',
        ])->assertOk();

        $doc->refresh();
        $this->assertSame(Document::STATUS_SUBMITTED, $doc->status);
        $this->assertNull($doc->jalur);
        $this->assertDatabaseHas('webhook_logs', ['notification_type' => 'Informasi']);
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
            '*user/login*' => Http::response([
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

    public function test_document_party_helper_methods(): void
    {
        $user = $this->authedUser();

        // 1. Test BC30 (Ekspor)
        $docBc30 = $user->documents()->create([
            'doc_type' => 'BC30',
            'status' => Document::STATUS_DRAFT,
            'payload' => [
                'header' => [
                    'eksportir' => [
                        'nama' => 'PT Ekspor Indonesia',
                        'npwp' => '111111111111111',
                    ],
                    'nitku' => '0000000000000001',
                ],
            ],
        ]);
        $this->assertSame('PT Ekspor Indonesia', $docBc30->partyName());
        $this->assertSame('111111111111111', $docBc30->partyNpwp());
        $this->assertSame('0000000000000001', $docBc30->partyNitku());

        // 2. Test BC20 (Impor)
        $docBc20 = $user->documents()->create([
            'doc_type' => 'BC20',
            'status' => Document::STATUS_DRAFT,
            'payload' => [
                'header' => [
                    'importir' => [
                        'nama' => 'PT Impor Indonesia',
                        'npwp' => '222222222222222',
                    ],
                ],
            ],
        ]);
        $this->assertSame('PT Impor Indonesia', $docBc20->partyName());
        $this->assertSame('222222222222222', $docBc20->partyNpwp());
        $this->assertNull($docBc20->partyNitku());

        // 3. Test TPB
        $docTpb = $user->documents()->create([
            'doc_type' => 'TPB',
            'status' => Document::STATUS_DRAFT,
            'payload' => [
                'header' => [
                    'pengusaha_tpb' => [
                        'nama' => 'PT TPB Nusantara',
                        'npwp' => '333333333333333',
                    ],
                ],
            ],
        ]);
        $this->assertSame('PT TPB Nusantara', $docTpb->partyName());
        $this->assertSame('333333333333333', $docTpb->partyNpwp());

        // 4. Test RUSH
        $docRush = $user->documents()->create([
            'doc_type' => 'RUSH',
            'status' => Document::STATUS_DRAFT,
            'payload' => [
                'header' => [
                    'pemohon' => [
                        'nama' => 'PT Rush Express',
                        'npwp' => '444444444444444',
                    ],
                ],
            ],
        ]);
        $this->assertSame('PT Rush Express', $docRush->partyName());
        $this->assertSame('444444444444444', $docRush->partyNpwp());

        // 5. Test Arsip
        $docArsip = $user->documents()->create([
            'doc_type' => 'BC30',
            'source' => Document::SOURCE_ARSIP,
            'status' => Document::STATUS_ACCEPTED,
            'payload' => [
                'nama_perusahaan' => 'PT Arsip Legacy',
                'npwp' => '555555555555555',
            ],
        ]);
        $this->assertSame('PT Arsip Legacy', $docArsip->partyName());
        $this->assertSame('555555555555555', $docArsip->partyNpwp());
    }

    public function test_daftar_dokumen_shows_ceisa_portal_columns(): void
    {
        $user = $this->authedUser();

        CeisaReference::create([
            'type' => 'kantor_pabean',
            'code' => '011200',
            'label' => '011200 - KPPBC TMP C Kuala Tanjung',
            'active' => true,
        ]);
        Cache::forget('ceisa.kantor_pabean_map');

        $doc = $user->documents()->create([
            'doc_type' => 'BC30',
            'status' => Document::STATUS_ACCEPTED,
            'nomor_aju' => '000020MOT83720260615000033',
            'nomor_daftar' => '018331',
            'jalur' => Document::JALUR_HIJAU,
            'payload' => [
                'kantor_pabean' => '011200',
                'header' => ['eksportir' => ['nama' => 'PT ATS Inti Sampoerna', 'npwp' => '111111111111111']],
            ],
            'ceisa_response' => [
                'nama_respon' => 'SPPB',
                'nomor_surat' => '018303/KBC.0201/2026',
                'tanggal_daftar' => '2026-06-12',
            ],
            'response_at' => now(),
        ]);

        // Helper derivasi (ground-truth Portal CEISA 4.0)
        $resp = $doc->responseSummary();
        $this->assertSame('SPPB', $resp['nama']);
        $this->assertSame('018303/KBC.0201/2026', $resp['no_surat']);
        $this->assertSame('011200 - KPPBC TMP C Kuala Tanjung', $doc->kantorPabeanLabel());
        $this->assertNotNull($doc->tanggalDaftar());

        // Halaman Daftar Dokumen menampilkan kolom-kolom portal
        $this->actingAs($user)
            ->get(route('documents.index'))
            ->assertOk()
            ->assertSee('018331')                       // nomor pendaftaran
            ->assertSee('SPPB')                          // nama respon
            ->assertSee('018303/KBC.0201/2026')          // no. surat respon
            ->assertSee('KPPBC TMP C Kuala Tanjung');    // label kantor pabean
    }

    public function test_detail_dokumen_shows_tracking_tabs(): void
    {
        $user = $this->authedUser();

        $doc = $user->documents()->create([
            'doc_type' => 'BC30',
            'status' => Document::STATUS_ACCEPTED,
            'nomor_aju' => '000020MOT83720260615000033',
            'nomor_daftar' => '018331',
            'jalur' => Document::JALUR_HIJAU,
            'payload' => ['header' => ['eksportir' => ['nama' => 'PT ATS Inti Sampoerna']]],
            'ceisa_response' => ['nama_respon' => 'SPPB', 'nomor_surat' => '018303/KBC.0201/2026'],
            'submitted_at' => now()->subDay(),
            'response_at' => now(),
        ]);

        $doc->webhookLogs()->create([
            'event' => 'BILLING',
            'nomor_aju' => $doc->nomor_aju,
            'payload' => ['nama_respon' => 'BILLING', 'nomor_surat' => 'BILL/2026/001'],
            'received_at' => now()->subHours(2),
        ]);

        // Helper timeline & respon
        $this->assertNotEmpty($doc->statusTimeline());
        $this->assertSame('Perekaman Dokumen', $doc->statusTimeline()[0]['label']);
        $namaRespon = array_column($doc->responseHistory(), 'nama');
        $this->assertContains('SPPB', $namaRespon);
        $this->assertContains('BILLING', $namaRespon);

        $this->actingAs($user)
            ->get(route('documents.show', $doc))
            ->assertOk()
            ->assertSee('Riwayat Status')
            ->assertSee('Riwayat Respon')
            ->assertSee('Riwayat Petugas')
            ->assertSee('Perekaman Dokumen')
            ->assertSee('SPPB');
    }

    public function test_kantor_pabean_resolves_from_ekspor_kantor_muat(): void
    {
        $user = $this->authedUser();

        CeisaReference::create([
            'type' => 'kantor_pabean',
            'code' => '050100',
            'label' => '050100 - KPU BC Tipe A Tanjung Priok',
            'active' => true,
        ]);
        Cache::forget('ceisa.kantor_pabean_map');

        // Dokumen ekspor BC 3.0 menyimpan kode kantor di header.kantor_muat
        $doc = $user->documents()->create([
            'doc_type' => 'BC30',
            'status' => Document::STATUS_DRAFT,
            'payload' => ['header' => ['kantor_muat' => '050100', 'eksportir' => ['nama' => 'PT Ekspor']]],
        ]);

        $this->assertSame('050100', $doc->kantorPabeanCode());
        $this->assertSame('050100 - KPU BC Tipe A Tanjung Priok', $doc->kantorPabeanLabel());
    }

    public function test_daftar_dokumen_action_bar_and_bumn_filter(): void
    {
        $user = $this->authedUser();

        $bumn = $user->documents()->create([
            'doc_type' => 'BC30',
            'status' => Document::STATUS_DRAFT,
            'payload' => ['bumn' => true, 'header' => ['eksportir' => ['nama' => 'PT BUMN Ekspor', 'npwp' => '111111111111111', 'nitku' => '0000000000000001']]],
        ]);
        $biasa = $user->documents()->create([
            'doc_type' => 'BC30',
            'status' => Document::STATUS_DRAFT,
            'payload' => ['header' => ['eksportir' => ['nama' => 'PT Swasta Biasa', 'npwp' => '222222222222222']]],
        ]);

        // Action bar tampil (toggle & menu ala portal)
        $this->actingAs($user)
            ->get(route('documents.index'))
            ->assertOk()
            ->assertSee('NPWP 16')
            ->assertSee('NITKU')
            ->assertSee('BUMN Ekspor')
            ->assertSee('Utilitas')
            ->assertSee('Monitoring')
            ->assertSee('Muat Ulang')
            ->assertSee('PT BUMN Ekspor')
            ->assertSee('PT Swasta Biasa');

        // Filter BUMN hanya menampilkan entitas BUMN
        $this->actingAs($user)
            ->get(route('documents.index', ['bumn' => 1]))
            ->assertOk()
            ->assertSee('PT BUMN Ekspor')
            ->assertDontSee('PT Swasta Biasa');
    }

    public function test_user_can_import_queried_document_from_ceisa_portal(): void
    {
        $user = $this->authedUser();

        $payload = [
            'nomor_aju' => '000020MOT83720260301000015',
            'nomor_daftar' => 'REG-12345',
            'jenis_doc' => 'BC20 — Pemberitahuan Impor Barang',
            'status' => 'DITERIMA / SPPB',
            'kantor' => 'KPPBC Belawan',
            'tanggal_daftar' => '2026-03-04',
            'nilai_pabean' => '12.500,00',
            'nama_perusahaan' => 'PT. Mora Multi Berkah',
            'uraian' => 'Mesin Pabrik',
        ];

        // 1. Sukses mengimpor dokumen
        $this->actingAs($user)
            ->post('/dokumen/import', $payload)
            ->assertRedirect();

        $doc = Document::where('nomor_aju', '000020MOT83720260301000015')->first();
        $this->assertNotNull($doc);
        $this->assertSame(Document::SOURCE_ARSIP, $doc->source);
        $this->assertSame('BC20', $doc->doc_type);
        $this->assertSame(Document::STATUS_ACCEPTED, $doc->status);
        $this->assertSame('REG-12345', $doc->nomor_daftar);
        $this->assertSame('PT. Mora Multi Berkah', data_get($doc->payload, 'nama_perusahaan'));
        $this->assertSame('KPPBC Belawan', data_get($doc->payload, 'kantor_pabean'));
        $this->assertEquals(12500.0, data_get($doc->payload, 'nilai'));
        $this->assertSame('Mesin Pabrik', data_get($doc->payload, 'uraian'));

        // 2. Proteksi anti-duplikasi
        $this->actingAs($user)
            ->post('/dokumen/import', $payload)
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_document_index_filters_by_type_status_and_search(): void
    {
        $user = $this->authedUser();

        $user->documents()->create([
            'doc_type' => 'BC30', 'source' => Document::SOURCE_H2H,
            'nomor_aju' => 'AJU-EKSPOR-1', 'payload' => ['x' => 1], 'status' => Document::STATUS_SUBMITTED,
        ]);
        $user->documents()->create([
            'doc_type' => 'BC20', 'source' => Document::SOURCE_H2H,
            'nomor_aju' => 'AJU-IMPOR-1', 'payload' => ['x' => 2], 'status' => Document::STATUS_DRAFT,
        ]);

        // Filter jenis BC30 -> hanya dokumen ekspor.
        $this->actingAs($user)->get(route('documents.index', ['doc_type' => 'BC30']))
            ->assertOk()
            ->assertSee('AJU-EKSPOR-1')
            ->assertDontSee('AJU-IMPOR-1');

        // Filter status draft -> hanya dokumen impor.
        $this->actingAs($user)->get(route('documents.index', ['status' => 'draft']))
            ->assertOk()
            ->assertSee('AJU-IMPOR-1')
            ->assertDontSee('AJU-EKSPOR-1');

        // Pencarian nomor aju.
        $this->actingAs($user)->get(route('documents.index', ['q' => 'IMPOR']))
            ->assertOk()
            ->assertSee('AJU-IMPOR-1')
            ->assertDontSee('AJU-EKSPOR-1');
    }

    public function test_user_can_duplicate_document_into_new_draft(): void
    {
        $user = $this->authedUser();
        $original = $user->documents()->create([
            'doc_type' => 'BC30', 'source' => Document::SOURCE_H2H,
            'nomor_aju' => '000001-PEB', 'nomor_daftar' => 'REG-1',
            'payload' => ['header' => ['eksportir' => ['nama' => 'PT M2B']]],
            'status' => Document::STATUS_ACCEPTED, 'jalur' => Document::JALUR_HIJAU,
        ]);

        $this->actingAs($user)
            ->post(route('documents.duplicate', $original))
            ->assertRedirect();

        $this->assertSame(2, $user->documents()->count());

        $clone = Document::latest('id')->first();
        $this->assertNotSame($original->id, $clone->id);
        $this->assertSame('BC30', $clone->doc_type);
        $this->assertSame(Document::STATUS_DRAFT, $clone->status);
        $this->assertNull($clone->nomor_aju);
        $this->assertNull($clone->nomor_daftar);
        $this->assertNull($clone->jalur);
        $this->assertSame('PT M2B', data_get($clone->payload, 'header.eksportir.nama'));
    }

    public function test_archived_document_cannot_be_duplicated(): void
    {
        $user = $this->authedUser();
        $arsip = $user->documents()->create([
            'doc_type' => 'BC20', 'source' => Document::SOURCE_ARSIP,
            'nomor_aju' => 'ARSIP-1', 'payload' => ['nama_perusahaan' => 'PT Lama'],
            'status' => Document::STATUS_ACCEPTED,
        ]);

        $this->actingAs($user)
            ->post(route('documents.duplicate', $arsip))
            ->assertSessionHas('error');

        $this->assertSame(1, $user->documents()->count());
    }

    public function test_user_cannot_duplicate_other_users_document(): void
    {
        $owner = $this->authedUser();
        $doc = $owner->documents()->create([
            'doc_type' => 'BC30', 'source' => Document::SOURCE_H2H,
            'payload' => ['x' => 1], 'status' => Document::STATUS_DRAFT,
        ]);

        $intruder = $this->authedUser();
        $this->actingAs($intruder)
            ->post(route('documents.duplicate', $doc))
            ->assertForbidden();
    }

    public function test_document_index_shows_rekap_counts(): void
    {
        $user = $this->authedUser();
        $user->documents()->create(['doc_type' => 'BC30', 'source' => Document::SOURCE_H2H, 'nomor_aju' => 'A1', 'payload' => ['x' => 1], 'status' => Document::STATUS_ACCEPTED, 'jalur' => Document::JALUR_MERAH]);
        $user->documents()->create(['doc_type' => 'BC20', 'source' => Document::SOURCE_H2H, 'nomor_aju' => 'A2', 'payload' => ['x' => 1], 'status' => Document::STATUS_ERROR]);

        $this->actingAs($user)->get(route('documents.index'))
            ->assertOk()
            ->assertViewHas('rekap', fn ($r) => $r['total'] === 2 && $r['accepted'] === 1 && $r['rejected'] === 1 && $r['merah'] === 1);
    }

    public function test_document_export_csv_respects_filters(): void
    {
        $user = $this->authedUser();
        $user->documents()->create(['doc_type' => 'BC30', 'source' => Document::SOURCE_H2H, 'nomor_aju' => 'EKSPOR-CSV', 'payload' => ['header' => ['eksportir' => ['nama' => 'PT M2B']]], 'status' => Document::STATUS_SUBMITTED]);
        $user->documents()->create(['doc_type' => 'BC20', 'source' => Document::SOURCE_H2H, 'nomor_aju' => 'IMPOR-CSV', 'payload' => ['x' => 1], 'status' => Document::STATUS_DRAFT]);

        $response = $this->actingAs($user)->get(route('documents.export', ['doc_type' => 'BC30']));
        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('No. Aju', $csv); // header row
        $this->assertStringContainsString('EKSPOR-CSV', $csv);
        $this->assertStringContainsString('PT M2B', $csv);
        $this->assertStringNotContainsString('IMPOR-CSV', $csv); // tersaring oleh filter BC30
    }

    public function test_download_respon_pdf_from_ceisa(): void
    {
        $user = $this->authedUser();
        $user->ceisaCredential()->create([
            'username' => 'm2b_user',
            'password' => 'm2b_pass',
            'api_key' => 'secret-key',
            'token' => 'VALID-TOKEN',
            'token_expires_at' => now()->addMinutes(10),
        ]);

        $doc = $user->documents()->create([
            'doc_type' => 'BC30',
            'source' => Document::SOURCE_H2H,
            'nomor_aju' => '000001-PEB',
            'payload' => ['x' => 1],
            'status' => Document::STATUS_ACCEPTED,
            'ceisa_response' => [
                'data' => [
                    'responPdf' => '/some/path/to/respon.pdf',
                ],
            ],
        ]);

        Http::fake([
            '*/openapi/download-respon*' => Http::response('PDF-CONTENT', 200, ['Content-Type' => 'application/pdf']),
        ]);

        $response = $this->actingAs($user)
            ->get(route('documents.download-respon', $doc));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame('PDF-CONTENT', $response->streamedContent());
    }

    public function test_cetak_formulir_pdf_from_ceisa(): void
    {
        $user = $this->authedUser();
        $user->ceisaCredential()->create([
            'username' => 'm2b_user',
            'password' => 'm2b_pass',
            'api_key' => 'secret-key',
            'token' => 'VALID-TOKEN',
            'token_expires_at' => now()->addMinutes(10),
        ]);

        $doc = $user->documents()->create([
            'doc_type' => 'BC30',
            'source' => Document::SOURCE_H2H,
            'nomor_aju' => '000001-PEB',
            'payload' => ['x' => 1],
            'status' => Document::STATUS_ACCEPTED,
        ]);

        Http::fake([
            '*/openapi/respon/cetak-formulir*' => Http::response('FORM-PDF', 200, ['Content-Type' => 'application/pdf']),
        ]);

        $response = $this->actingAs($user)
            ->get(route('documents.cetak-formulir', $doc));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame('FORM-PDF', $response->streamedContent());
    }

    public function test_download_billing_pdf_from_ceisa(): void
    {
        $user = $this->authedUser();
        $user->ceisaCredential()->create([
            'username' => 'm2b_user',
            'password' => 'm2b_pass',
            'api_key' => 'secret-key',
            'token' => 'VALID-TOKEN',
            'token_expires_at' => now()->addMinutes(10),
        ]);

        $doc = $user->documents()->create([
            'doc_type' => 'BC30',
            'source' => Document::SOURCE_H2H,
            'nomor_aju' => '000001-PEB',
            'payload' => ['x' => 1],
            'status' => Document::STATUS_ACCEPTED,
            'ceisa_response' => [
                'data' => [
                    'kodeBilling' => '98765432101',
                ],
            ],
        ]);

        Http::fake([
            '*/openapi/respon/billing*' => Http::response('BILLING-PDF', 200, ['Content-Type' => 'application/pdf']),
        ]);

        $response = $this->actingAs($user)
            ->get(route('documents.download-billing', $doc));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame('BILLING-PDF', $response->streamedContent());
    }

    public function test_user_can_edit_draft_document(): void
    {
        $user = $this->authedUser();
        $user->ceisaCredential()->create([
            'username' => 'm2b_user', 'password' => 'm2b_pass', 'api_key' => 'KEY-123',
        ]);

        $doc = $user->documents()->create([
            'doc_type' => 'BC30', 'source' => Document::SOURCE_H2H,
            'payload' => ['header' => ['eksportir' => ['nama' => 'PT Lama']]],
            'status' => Document::STATUS_DRAFT,
        ]);

        // Halaman edit dapat diakses & ter-pre-fill.
        $this->actingAs($user)->get(route('documents.edit', $doc))->assertOk();

        // Simpan perubahan sebagai draft (tidak submit ke CEISA).
        $this->actingAs($user)
            ->put(route('documents.update', $doc), $this->bc30Payload([
                'nama_eksportir' => 'PT Baru Jaya',
                'submit_action' => 'draft',
            ]))
            ->assertRedirect(route('documents.show', $doc));

        $doc->refresh();
        $this->assertSame('PT Baru Jaya', data_get($doc->payload, 'header.eksportir.nama'));
        $this->assertSame(Document::STATUS_DRAFT, $doc->status);
        $this->assertSame(1, $user->documents()->count());
    }

    public function test_submitted_document_cannot_be_edited(): void
    {
        $user = $this->authedUser();
        $doc = $user->documents()->create([
            'doc_type' => 'BC30', 'source' => Document::SOURCE_H2H,
            'nomor_aju' => 'AJU-LIVE', 'payload' => ['header' => []],
            'status' => Document::STATUS_SUBMITTED,
        ]);

        $this->actingAs($user)
            ->get(route('documents.edit', $doc))
            ->assertRedirect(route('documents.show', $doc))
            ->assertSessionHas('error');
    }

    public function test_user_can_delete_draft_document(): void
    {
        $user = $this->authedUser();
        $doc = $user->documents()->create([
            'doc_type' => 'BC30', 'source' => Document::SOURCE_H2H,
            'payload' => ['x' => 1], 'status' => Document::STATUS_DRAFT,
        ]);

        $this->actingAs($user)
            ->delete(route('documents.destroy', $doc))
            ->assertRedirect(route('documents.index'));

        $this->assertSame(0, $user->documents()->count());
    }

    public function test_submitted_document_cannot_be_deleted(): void
    {
        $user = $this->authedUser();
        $doc = $user->documents()->create([
            'doc_type' => 'BC30', 'source' => Document::SOURCE_H2H,
            'nomor_aju' => 'AJU-LIVE', 'payload' => ['x' => 1],
            'status' => Document::STATUS_ACCEPTED,
        ]);

        $this->actingAs($user)
            ->delete(route('documents.destroy', $doc))
            ->assertSessionHas('error');

        $this->assertSame(1, $user->documents()->count());
    }

    public function test_user_cannot_delete_other_users_document(): void
    {
        $owner = $this->authedUser();
        $doc = $owner->documents()->create([
            'doc_type' => 'BC30', 'source' => Document::SOURCE_H2H,
            'payload' => ['x' => 1], 'status' => Document::STATUS_DRAFT,
        ]);

        $this->actingAs($this->authedUser())
            ->delete(route('documents.destroy', $doc))
            ->assertForbidden();

        $this->assertSame(1, Document::count());
    }

    public function test_user_can_update_archived_document(): void
    {
        $user = $this->authedUser();
        $arsip = $user->documents()->create([
            'doc_type' => 'BC30', 'source' => Document::SOURCE_ARSIP,
            'nomor_aju' => 'ARSIP-9', 'payload' => ['nama_perusahaan' => 'PT Lama'],
            'status' => Document::STATUS_ACCEPTED,
        ]);

        $this->actingAs($user)
            ->put(route('documents.archive.update', $arsip), [
                'doc_type' => 'BC30',
                'nomor_aju' => 'ARSIP-9',
                'status' => 'accepted',
                'nama_perusahaan' => 'PT Sudah Diperbaiki',
            ])
            ->assertRedirect(route('documents.show', $arsip));

        $arsip->refresh();
        $this->assertSame('PT Sudah Diperbaiki', data_get($arsip->payload, 'nama_perusahaan'));
    }

    public function test_bc20_carries_transport_and_transaction_fields(): void
    {
        Http::fake([
            '*user/login*' => Http::response(['access_token' => 'TOK', 'expires_in' => 3600], 200),
            '*/openapi/document*' => Http::response(['status' => 'OK', 'idHeader' => 'uuid-imp'], 200),
        ]);

        $user = $this->authedUser();
        $credential = $user->ceisaCredential()->create([
            'username' => 'm2b_user', 'password' => 'm2b_pass', 'api_key' => 'KEY-123', 'npwp' => '012345678901000',
        ]);

        $this->actingAs($user)->post('/dokumen/submit', [
            'doc_type' => 'BC20',
            'nama_importir' => 'PT Importir Jaya', 'npwp_importir' => '0123456789012000', 'alamat_importir' => 'Jakarta',
            'nib_importir' => 'NIB-99', 'jenis_api' => '02',
            'nama_pemasok' => 'Acme Inc', 'negara_pemasok' => 'SG',
            'pelabuhan_muat' => 'SGSIN', 'pelabuhan_bongkar' => 'IDTPP',
            'cara_angkut' => 'Udara', 'nama_sarana' => 'GA-880', 'voy_flight' => 'GA880', 'kode_bendera' => 'id',
            'kode_tps' => 'TPS-XYZ', 'tanggal_tiba' => '2026-07-01',
            'kode_valuta' => 'USD', 'ndpbm' => 16250, 'incoterm' => 'cif', 'nilai_cif' => 2000,
            'freight' => 150, 'nilai_asuransi' => 20, 'bruto' => 88,
            'pernyataan_nama' => 'Budi', 'pernyataan_jabatan' => 'Manajer Impor',
            'barang' => [[
                'hs_code' => '8471.30.20', 'uraian' => 'Laptop', 'jumlah_satuan' => 5,
                'kode_satuan' => 'UNT', 'netto' => 10, 'nilai_cif' => 2000,
            ]],
        ])->assertRedirect();

        $doc = Document::first();
        $this->assertEquals(16250, data_get($doc->payload, 'header.ndpbm'));
        $this->assertSame('CIF', data_get($doc->payload, 'header.incoterm'));
        $this->assertSame('GA-880', data_get($doc->payload, 'header.pengangkutan.sarana_angkut'));
        $this->assertSame('ID', data_get($doc->payload, 'header.pengangkutan.bendera'));
        $this->assertSame('NIB-99', data_get($doc->payload, 'header.importir.nib'));

        // Builder memakai nilai form (bukan dummy 15800/FOB/MV CONTAINER).
        $flat = CeisaPayloadBuilder::make()->build('BC20', $doc->payload, 'AJU-X');
        $this->assertEqualsWithDelta(16250.0, $flat['ndpbm'], 0.01);
        $this->assertSame('CIF', $flat['kodeIncoterm']);
        $this->assertSame('TPS-XYZ', $flat['kodeTps']);
        $this->assertSame('GA-880', $flat['pengangkut'][0]['namaPengangkut']);
        $this->assertSame('ID', $flat['pengangkut'][0]['kodeBendera']);
        $this->assertSame('4', $flat['pengangkut'][0]['kodeCaraAngkut']); // Udara
        $this->assertEqualsWithDelta(150.0, $flat['freight'], 0.01);
        $this->assertSame('Budi', $flat['namaTtd']);
        $this->assertSame('NIB-99', $flat['entitas'][0]['nibEntitas']);
        $this->assertSame('02', $flat['entitas'][0]['kodeJenisApi']);
    }

    public function test_tpb_and_rush_carry_transport_and_office_fields(): void
    {
        Http::fake([
            '*user/login*' => Http::response(['access_token' => 'TOK', 'expires_in' => 3600], 200),
            '*/openapi/document*' => Http::response(['status' => 'OK', 'idHeader' => 'uuid-tpb-rush'], 200),
        ]);

        $user = $this->authedUser();
        $user->ceisaCredential()->create([
            'username' => 'm2b_user', 'password' => 'm2b_pass', 'api_key' => 'KEY-123', 'npwp' => '012345678901000',
        ]);

        // 1. Submit TPB
        $this->actingAs($user)->post('/dokumen/submit', [
            'doc_type' => 'TPB',
            'nama_tpb' => 'PT TPB Berikat', 'npwp_tpb' => '333333333333333', 'alamat_tpb' => 'Cikarang',
            'jenis_tpb' => 'Kawasan Berikat', 'tujuan_tpb' => 'Pemasukan', 'dokumen_referensi' => 'REF-123',
            'kode_valuta' => 'USD', 'nilai_barang' => 5000,
            'kode_kantor' => '040300', 'cara_angkut' => 'Laut', 'nama_sarana' => 'MV BINTANG', 'voy_flight' => 'V-99', 'kode_bendera' => 'sg',
            'barang' => [[
                'hs_code' => '8471.30.20', 'uraian' => 'Laptop', 'jumlah_satuan' => 5,
                'kode_satuan' => 'UNT', 'netto' => 10, 'nilai_barang' => 5000,
            ]],
        ])->assertRedirect();

        $docTpb = Document::where('doc_type', 'TPB')->first();
        $this->assertSame('040300', data_get($docTpb->payload, 'header.kode_kantor'));
        $this->assertSame('MV BINTANG', data_get($docTpb->payload, 'header.pengangkutan.sarana_angkut'));

        $flatTpb = CeisaPayloadBuilder::make()->build('TPB', $docTpb->payload, 'AJU-TPB-1');
        $this->assertSame('040300', $flatTpb['kodeKantor']);
        $this->assertSame('MV BINTANG', $flatTpb['pengangkut'][0]['namaPengangkut']);
        $this->assertSame('SG', $flatTpb['pengangkut'][0]['kodeBendera']);
        $this->assertSame('1', $flatTpb['pengangkut'][0]['kodeCaraAngkut']);

        // 2. Submit RUSH
        $this->actingAs($user)->post('/dokumen/submit', [
            'doc_type' => 'RUSH',
            'nama_pemohon' => 'PT Rush Express', 'npwp_pemohon' => '444444444444444', 'alamat_pemohon' => 'Bandara Soetta',
            'nama_sarana_pengangkut' => 'Singapore Airlines', 'nomor_flight' => 'SQ-123',
            'nomor_awb_bl' => 'AWB-999', 'tanggal_awb_bl' => '2026-07-02',
            'alasan_segera' => 'Vaksin', 'jumlah_kemasan' => 5, 'jenis_kemasan' => 'BX',
            'kode_kantor' => '050100', 'kode_bendera' => 'sg', 'cara_angkut' => 'Udara',
            'barang' => [[
                'hs_code' => '3002.20.00', 'uraian' => 'Vaksin', 'jumlah_satuan' => 1000,
                'kode_satuan' => 'VLS', 'netto' => 50, 'nilai_barang' => 15000,
            ]],
        ])->assertRedirect();

        $docRush = Document::where('doc_type', 'RUSH')->first();
        $this->assertSame('050100', data_get($docRush->payload, 'header.kode_kantor'));
        $this->assertSame('Singapore Airlines', data_get($docRush->payload, 'header.pengangkutan.sarana'));
        $this->assertSame('SG', data_get($docRush->payload, 'header.pengangkutan.bendera'));

        $flatRush = CeisaPayloadBuilder::make()->build('RUSH', $docRush->payload, 'AJU-RUSH-1');
        $this->assertSame('050100', $flatRush['kodeKantor']);
        $this->assertSame('Singapore Airlines', $flatRush['pengangkut'][0]['namaPengangkut']);
        $this->assertSame('SG', $flatRush['pengangkut'][0]['kodeBendera']);
        $this->assertSame('4', $flatRush['pengangkut'][0]['kodeCaraAngkut']);
    }

    public function test_settings_page_shows_token_countdown(): void
    {
        $user = $this->authedUser();
        $user->ceisaCredential()->create([
            'username' => 'm2b_user', 'password' => 'm2b_pass', 'api_key' => 'KEY-123',
            'token' => 'TOK-LIVE', 'token_expires_at' => now()->addMinutes(5),
        ]);

        $this->actingAs($user)
            ->get(route('settings.ceisa.edit'))
            ->assertOk()
            ->assertSee('Status token akses')
            ->assertSee('ceisaTokenCountdown', false);
    }

    public function test_notifications_page_groups_djbc_webhook_logs(): void
    {
        $user = $this->authedUser();
        $doc = $user->documents()->create([
            'doc_type' => 'BC30', 'source' => Document::SOURCE_H2H,
            'nomor_aju' => 'AJU-N1', 'payload' => ['x' => 1], 'status' => Document::STATUS_SUBMITTED,
        ]);

        WebhookLog::create([
            'document_id' => $doc->id, 'event' => 'SPPB Terbit', 'notification_type' => 'Respon',
            'nomor_aju' => 'AJU-N1', 'payload' => ['status' => 'SPPB', 'message' => 'Barang dapat dikeluarkan'],
            'received_at' => now(),
        ]);

        // Notifikasi milik user lain tidak boleh tampil.
        $otherDoc = $this->authedUser()->documents()->create([
            'doc_type' => 'BC30', 'source' => Document::SOURCE_H2H,
            'nomor_aju' => 'AJU-OTHER', 'payload' => ['x' => 1], 'status' => Document::STATUS_SUBMITTED,
        ]);
        WebhookLog::create([
            'document_id' => $otherDoc->id, 'event' => 'RAHASIA', 'notification_type' => 'Respon',
            'nomor_aju' => 'AJU-OTHER', 'payload' => ['status' => 'X'], 'received_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Pusat Notifikasi DJBC')
            ->assertSee('SPPB Terbit')
            ->assertDontSee('RAHASIA');
    }

    public function test_refresh_status_pulls_and_applies_from_ceisa(): void
    {
        Http::fake([
            '*user/login*' => Http::response(['access_token' => 'TOK', 'expires_in' => 3600], 200),
            '*/openapi/status/*' => Http::response([
                'status' => 'SPPB DITERBITKAN',
                'jalur' => 'HIJAU',
                'nomor_daftar' => 'REG-99',
            ], 200),
        ]);

        $user = $this->authedUser();
        $user->ceisaCredential()->create([
            'username' => 'm2b_user', 'password' => 'm2b_pass', 'api_key' => 'KEY-123',
        ]);

        $doc = $user->documents()->create([
            'doc_type' => 'BC30', 'source' => Document::SOURCE_H2H,
            'nomor_aju' => 'AJU-1', 'id_header' => 'uuid-1',
            'payload' => ['x' => 1], 'status' => Document::STATUS_SUBMITTED,
        ]);

        $this->actingAs($user)
            ->post(route('documents.refresh-status', $doc))
            ->assertRedirect();

        $doc->refresh();
        $this->assertSame(Document::STATUS_ACCEPTED, $doc->status);
        $this->assertSame(Document::JALUR_HIJAU, $doc->jalur);
        $this->assertSame('REG-99', $doc->nomor_daftar);

        // idHeader ikut dikirim sebagai query saat menarik status.
        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/openapi/status/AJU-1')
            && str_contains($r->url(), 'idHeader=uuid-1'));
    }

    public function test_sync_references_command_upserts_from_ceisa(): void
    {
        config(['ceisa.reference_endpoints' => ['negara' => '/v2/openapi/referensi/negara']]);

        Http::fake([
            '*user/login*' => Http::response(['access_token' => 'TOK', 'expires_in' => 3600], 200),
            '*referensi/negara*' => Http::response([
                'data' => [
                    ['kodeNegara' => 'SG', 'uraianNegara' => 'Singapura'],
                    ['kodeNegara' => 'ID', 'uraianNegara' => 'Indonesia'],
                ],
            ], 200),
        ]);

        $user = $this->authedUser();
        $user->ceisaCredential()->create([
            'username' => 'm2b_user', 'password' => 'm2b_pass', 'api_key' => 'KEY-123',
        ]);

        $this->artisan('ceisa:sync-references', ['--type' => ['negara']])
            ->assertExitCode(0);

        $this->assertDatabaseHas('ceisa_references', ['type' => 'negara', 'code' => 'SG', 'label' => 'Singapura', 'active' => true]);
        $this->assertDatabaseHas('ceisa_references', ['type' => 'negara', 'code' => 'ID', 'label' => 'Indonesia']);
    }

    public function test_sync_references_command_skips_when_no_endpoints_mapped(): void
    {
        config(['ceisa.reference_endpoints' => []]);
        $user = $this->authedUser();
        $user->ceisaCredential()->create([
            'username' => 'm2b_user', 'password' => 'm2b_pass', 'api_key' => 'KEY-123',
        ]);
        $this->artisan('ceisa:sync-references')->assertExitCode(0);
    }

    public function test_probe_status_command_executes_requests(): void
    {
        Http::fake([
            '*user/login*' => Http::response(['access_token' => 'TOK', 'expires_in' => 3600], 200),
            '*/status*' => Http::response(['status' => 'OK', 'data' => []], 200),
            '*/document*' => Http::response(['status' => 'OK', 'data' => []], 200),
        ]);

        $user = $this->authedUser();
        $user->ceisaCredential()->create([
            'username' => 'm2b_user', 'password' => 'm2b_pass', 'api_key' => 'KEY-123', 'npwp' => '123456789012345'
        ]);

        $this->artisan('ceisa:probe-status')
            ->assertExitCode(0);

        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/status') && ($r->toPsrRequest()->getUri()->getQuery() === 'idPerusahaan=123456789012345' || str_contains($r->url(), 'idPerusahaan=123456789012345')));
        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/status') && ($r->toPsrRequest()->getUri()->getQuery() === 'npwp=123456789012345' || str_contains($r->url(), 'npwp=123456789012345')));
        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/document'));
    }

    public function test_sync_documents_pulls_and_creates_records(): void
    {
        Http::fake([
            '*user/login*' => Http::response(['access_token' => 'TOK', 'expires_in' => 3600], 200),
            '*/status*' => Http::response([
                'status' => 'Success',
                'dataStatus' => [
                    [
                        'nomorAju' => '04012001234567890123456789',
                        'status' => 'TERIMA',
                        'waktuStatus' => '2026-06-30 19:42:00',
                        'idHeader' => 'id-1',
                        'jalur' => 'H',
                    ]
                ],
                'dataRespon' => [
                    [
                        'nomorAju' => '04012001234567890123456789',
                        'nomorDaftar' => '001234',
                        'tanggalDaftar' => '2026-06-30',
                        'kodeDokumen' => '20',
                        'responPdf' => 'path/to/pdf',
                        'kodeBilling' => 'bill-1',
                    ]
                ]
            ], 200),
        ]);

        $user = $this->authedUser();
        $user->ceisaCredential()->create([
            'username' => 'm2b_user', 'password' => 'm2b_pass', 'api_key' => 'KEY-123', 'npwp' => '0123456789012345'
        ]);

        $this->actingAs($user)
            ->post(route('documents.sync'))
            ->assertRedirect();

        $this->assertDatabaseHas('documents', [
            'user_id' => $user->id,
            'nomor_aju' => '04012001234567890123456789',
            'doc_type' => 'BC20',
            'status' => Document::STATUS_ACCEPTED,
            'jalur' => Document::JALUR_HIJAU,
            'nomor_daftar' => '001234',
            'id_header' => 'id-1',
        ]);
    }
}
