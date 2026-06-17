<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use App\Services\CeisaService;
use Database\Seeders\CeisaReferenceSeeder;
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
            return str_contains($request->url(), '/nle-oauth/v1/user/login')
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
            return str_contains($request->url(), '/nle-oauth/v1/user/login')
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
            return str_starts_with($request->url(), 'https://custom-gateway.example.com/nle-oauth/v1/user/login');
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

        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/nle-oauth/v1/user/update-token')
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
                    'responPdf' => '/some/path/to/respon.pdf'
                ]
            ]
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
                    'kodeBilling' => '98765432101'
                ]
            ]
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
}
