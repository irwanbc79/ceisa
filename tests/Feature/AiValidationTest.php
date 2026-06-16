<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use App\Services\AI\HybridAiClient;
use App\Services\DocumentValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function authedUser(): User
    {
        return User::factory()->create(['role' => User::ROLE_OPERATOR]);
    }

    protected function makeDocument(User $user, array $payload = []): Document
    {
        return $user->documents()->create([
            'doc_type' => 'BC30',
            'source' => Document::SOURCE_H2H,
            'status' => Document::STATUS_DRAFT,
            'payload' => $payload ?: [
                'header' => ['eksportir' => ['nama' => 'PT M2B', 'npwp' => '0011223344556000']],
                'barang' => [[
                    'seri' => 1, 'hs_code' => '12', 'uraian' => 'x',
                    'jumlah_satuan' => 0, 'kode_satuan' => 'PCE', 'netto' => 0, 'nilai_fob' => 0,
                ]],
            ],
        ]);
    }

    public function test_rule_checks_run_without_ai_and_flag_bad_data(): void
    {
        config(['ai.enabled' => false]);
        $user = $this->authedUser();
        $doc = $this->makeDocument($user);

        $result = (new DocumentValidator)->validate($doc);

        $this->assertNull($result['provider']);
        $messages = collect($result['rule_findings'])->pluck('message')->implode(' | ');
        $this->assertStringContainsString('8 digit', $messages); // HS code pendek
        $this->assertNotEmpty($result['rule_findings']);
    }

    public function test_hybrid_client_fails_over_to_next_provider(): void
    {
        config([
            'ai.order' => ['claude', 'gemini'],
            'ai.providers.claude.api_key' => 'k-claude',
            'ai.providers.gemini.api_key' => 'k-gemini',
        ]);

        Http::fake([
            'api.anthropic.com/*' => Http::response(['error' => 'boom'], 500),
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'HALO-GEMINI']]]]],
            ], 200),
        ]);

        $result = HybridAiClient::fromConfig()->chat('sys', 'user');

        $this->assertSame('gemini', $result['provider']);
        $this->assertSame('HALO-GEMINI', $result['text']);
    }

    public function test_validate_ai_action_returns_findings_from_claude(): void
    {
        config([
            'ai.enabled' => true,
            'ai.order' => ['claude'],
            'ai.providers.claude.api_key' => 'k-claude',
        ]);

        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => '```json
{"findings":[{"level":"warning","field":"barang #1","message":"HS code tidak konsisten dengan uraian"}]}
```']],
            ], 200),
        ]);

        $user = $this->authedUser();
        $doc = $this->makeDocument($user);

        $response = $this->actingAs($user)->post(route('documents.validate', $doc));
        $response->assertRedirect(route('documents.show', $doc));

        $av = session('ai_validation');
        $this->assertSame('claude', $av['provider']);
        $this->assertNull($av['ai_error']);
        $this->assertSame('HS code tidak konsisten dengan uraian', $av['ai_findings'][0]['message']);
        $this->assertSame('warning', $av['ai_findings'][0]['level']);
        $this->assertNotEmpty($av['rule_findings']);
    }

    public function test_validate_ai_reports_error_when_no_provider_configured(): void
    {
        config([
            'ai.enabled' => true,
            'ai.order' => ['claude', 'gemini', 'deepseek'],
            'ai.providers.claude.api_key' => null,
            'ai.providers.gemini.api_key' => null,
            'ai.providers.deepseek.api_key' => null,
        ]);

        $user = $this->authedUser();
        $doc = $this->makeDocument($user);

        $this->actingAs($user)->post(route('documents.validate', $doc))->assertRedirect();

        $av = session('ai_validation');
        $this->assertNull($av['provider']);
        $this->assertNotNull($av['ai_error']);
        $this->assertEmpty($av['ai_findings']);
        $this->assertNotEmpty($av['rule_findings']); // aturan tetap jalan
    }

    public function test_validate_ai_forbidden_for_other_users_document(): void
    {
        $owner = $this->authedUser();
        $doc = $this->makeDocument($owner);

        $intruder = $this->authedUser();
        $this->actingAs($intruder)->post(route('documents.validate', $doc))->assertForbidden();
    }
}
