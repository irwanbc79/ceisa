<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Visibilitas dokumen TIM: seluruh staf (admin & operator) melihat dan
 * mengelola semua dokumen perusahaan; user_id hanya mencatat pembuat.
 */
class TeamVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function operator(): User
    {
        return User::factory()->create(['role' => User::ROLE_OPERATOR]);
    }

    public function test_operator_sees_teammates_documents_in_list_and_detail(): void
    {
        $creator = $this->operator();
        $doc = $creator->documents()->create([
            'doc_type' => 'BC30', 'source' => Document::SOURCE_H2H,
            'nomor_aju' => 'AJU-TIM-1', 'payload' => ['x' => 1],
            'status' => Document::STATUS_SUBMITTED,
        ]);

        $teammate = $this->operator();

        $this->actingAs($teammate)
            ->get(route('documents.index'))
            ->assertOk()
            ->assertSee('AJU-TIM-1');

        $this->actingAs($teammate)
            ->get(route('documents.show', $doc))
            ->assertOk();

        $this->actingAs($teammate)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('AJU-TIM-1');
    }

    public function test_sync_by_second_operator_does_not_duplicate_documents(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $admin->ceisaCredential()->create([
            'username' => 'u', 'password' => 'p', 'api_key' => 'K',
            'npwp' => '960208833125000',
        ]);

        Http::fake([
            '*user/login*' => Http::response(['access_token' => 'TOK', 'expires_in' => 3600], 200),
            '*/status*' => Http::response([
                'status' => 'Success',
                'dataStatus' => [
                    ['nomorAju' => '000030MOT83720260525000009', 'nomorDaftar' => '003574', 'kodeProses' => '400', 'waktuStatus' => '2026-07-01 13:13:00', 'keterangan' => 'Pemeriksaan Dokumen', 'kodeDokumen' => '30'],
                ],
                'dataRespon' => [],
            ], 200),
        ]);

        $opA = $this->operator();
        $opB = $this->operator();

        $this->actingAs($opA)->post(route('documents.sync'))->assertRedirect();
        $this->actingAs($opB)->post(route('documents.sync'))->assertRedirect();

        // Dokumen yang sama TIDAK diduplikasi antar operator.
        $this->assertSame(1, Document::where('nomor_aju', '000030MOT83720260525000009')->count());
        // Pembuat tercatat = operator pertama yang menarik.
        $this->assertSame($opA->id, Document::first()->user_id);
    }
}
