<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CeisaSyncCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_fails_safely_without_company_credential(): void
    {
        $this->artisan('ceisa:sync-documents')
            ->expectsOutput('Kredensial CEISA perusahaan belum tersedia.')
            ->assertFailed();
    }

    public function test_command_synchronizes_documents_with_company_credential(): void
    {
        Http::fake([
            '*/openapi/status*' => Http::response([
                'dataStatus' => [],
                'dataRespon' => [],
            ]),
        ]);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $admin->ceisaCredential()->create([
            'username' => 'm2b_user',
            'password' => 'm2b_pass',
            'api_key' => 'secret-key',
            'npwp' => '012345678901000',
            'token' => 'TOKEN-XYZ',
            'token_expires_at' => now()->addHour(),
        ]);

        $this->artisan('ceisa:sync-documents')
            ->expectsOutput('Sinkronisasi CEISA selesai: 0 dokumen diperbarui.')
            ->assertSuccessful();

        Http::assertSentCount(1);
    }
}
