<?php

namespace Tests\Feature;

use App\Jobs\SubmitCeisaDocumentJob;
use App\Models\CeisaCredential;
use App\Models\Document;
use App\Models\User;
use App\Services\CeisaResponseExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SecurityAndHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_base_url_blocks_ssrf_targets(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        foreach ([
            'http://127.0.0.1:8080',
            'https://169.254.169.254/latest/meta-data',
            'https://10.0.0.1/api',
            'https://localhost/ceisa',
        ] as $unsafeUrl) {
            $response = $this->actingAs($admin)->post(route('settings.ceisa.update'), [
                'username' => 'm2b_user',
                'password' => 'secret-pass',
                'api_key' => 'secret-key',
                'environment' => 'custom',
                'custom_base_url' => $unsafeUrl,
            ]);

            $response->assertSessionHasErrors('custom_base_url');
        }
    }

    public function test_webhook_rejects_secret_in_query_string(): void
    {
        config(['ceisa.webhook_secret' => 'top-secret']);

        $this->postJson('/api/webhook/ceisa?secret=top-secret', [
            'nomor_aju' => '040130ABCDEF20261114000001',
            'status' => 'SPPB',
        ])->assertUnauthorized();
    }

    public function test_download_respon_and_billing_reject_unsafe_query_parameters(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OPERATOR]);
        CeisaCredential::create([
            'user_id' => $user->id,
            'username' => 'u',
            'password' => 'p',
            'api_key' => 'k',
            'base_url' => 'https://apisdev-gw.beacukai.go.id',
        ]);
        $doc = $user->documents()->create([
            'doc_type' => 'BC30',
            'source' => Document::SOURCE_H2H,
            'status' => Document::STATUS_SUBMITTED,
            'nomor_aju' => '040130ABCDEF20261114000002',
            'payload' => [],
        ]);

        $this->actingAs($user)
            ->get(route('documents.download-respon', $doc).'?path=../../etc/passwd')
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->actingAs($user)
            ->get(route('documents.download-billing', $doc).'?kode_billing=https://evil.test/x')
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_csv_export_neutralizes_formula_injection(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OPERATOR]);
        $user->documents()->create([
            'doc_type' => 'BC30',
            'source' => Document::SOURCE_H2H,
            'status' => Document::STATUS_DRAFT,
            'nomor_aju' => '040130ABCDEF20261114000003',
            'payload' => [
                'header' => ['eksportir' => ['nama' => '=CMD("calc")', 'npwp' => '012345678901000']],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('documents.export'));
        $response->assertOk();

        $csv = $response->streamedContent();
        $this->assertStringContainsString("'=CMD", $csv);
    }

    public function test_submit_job_reconciles_submitting_document_on_retry(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_OPERATOR]);
        CeisaCredential::create([
            'user_id' => $user->id,
            'username' => 'u',
            'password' => 'p',
            'api_key' => 'k',
            'npwp' => '012345678901000',
            'base_url' => 'https://apisdev-gw.beacukai.go.id',
            'token' => 'valid-token',
            'token_expires_at' => now()->addHour(),
        ]);

        $doc = $user->documents()->create([
            'doc_type' => 'BC30',
            'source' => Document::SOURCE_H2H,
            'status' => Document::STATUS_SUBMITTING,
            'nomor_aju' => '040130ABCDEF20261114000004',
            'payload' => [],
        ]);

        Http::fake([
            'apisdev-gw.beacukai.go.id/v2/openapi/status*' => Http::response([
                'dataStatus' => [[
                    'nomorAju' => '040130ABCDEF20261114000004',
                    'keterangan' => 'NPE',
                    'waktuStatus' => '2026-11-14 16:00:00',
                    'nomorDaftar' => '000999',
                ]],
                'dataRespon' => [[
                    'nomorAju' => '040130ABCDEF20261114000004',
                    'keterangan' => 'NPE',
                    'nomorDaftar' => '000999',
                ]],
            ], 200),
        ]);

        (new SubmitCeisaDocumentJob($doc))->handle();

        $doc->refresh();
        $this->assertSame(Document::STATUS_ACCEPTED, $doc->status);
        $this->assertSame('000999', $doc->nomor_daftar);
    }

    public function test_response_extractor_reads_pdf_and_billing_consistently(): void
    {
        $user = User::factory()->create();
        $doc = $user->documents()->create([
            'doc_type' => 'BC20',
            'source' => Document::SOURCE_H2H,
            'status' => Document::STATUS_ACCEPTED,
            'nomor_aju' => '040120ABCDEF20261114000005',
            'payload' => ['raw_respon' => ['pdf' => '/respon/2026/sppb.pdf']],
            'ceisa_response' => ['kode_billing' => '820261114000123'],
        ]);

        $this->assertSame('/respon/2026/sppb.pdf', CeisaResponseExtractor::responPdfPath($doc));
        $this->assertSame('820261114000123', CeisaResponseExtractor::kodeBilling($doc));
    }

    public function test_dashboard_and_document_show_render_with_ui_enhancements(): void
    {
        $user = User::factory()->create();
        $doc = $user->documents()->create([
            'doc_type' => 'BC30',
            'source' => Document::SOURCE_H2H,
            'status' => Document::STATUS_ACCEPTED,
            'jalur' => Document::JALUR_HIJAU,
            'nomor_aju' => '040130ABCDEF20261114000006',
            'payload' => ['header' => ['eksportir' => ['nama' => 'PT M2B']]],
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Distribusi Status Dokumen')
            ->assertSee('SPPB / NPE');

        $this->actingAs($user)
            ->get(route('documents.show', $doc))
            ->assertOk()
            ->assertSee('Selesai (SPPB / NPE)');
    }
}
