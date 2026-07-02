<?php

namespace Tests\Feature;

use App\Models\Manifest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManifestTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create();
    }

    public function test_guest_cannot_access_manifest_monitoring(): void
    {
        $this->get(route('manifests.index'))->assertRedirect(route('login'));
    }

    public function test_monitoring_shows_empty_state_when_no_data(): void
    {
        $this->actingAs($this->user())
            ->get(route('manifests.index'))
            ->assertOk()
            ->assertSee('Monitoring Manifes')
            ->assertSee('Belum ada data manifes');
    }

    public function test_monitoring_lists_and_filters_manifests(): void
    {
        $user = $this->user();
        Manifest::create([
            'user_id' => $user->id,
            'jenis_manifes' => 'inward',
            'nama_sarana' => 'MV SINAR JAYA',
            'nomor_voyage' => 'V-2401',
            'kode_kantor' => '010700',
            'tanggal_sarana' => '2026-06-20',
        ]);
        Manifest::create([
            'user_id' => $user->id,
            'jenis_manifes' => 'outward',
            'nama_sarana' => 'MV BINTANG TIMUR',
            'nomor_voyage' => 'V-9902',
            'tanggal_sarana' => '2026-06-21',
        ]);

        // Tampil semua.
        $this->actingAs($user)->get(route('manifests.index'))
            ->assertOk()
            ->assertSee('MV SINAR JAYA')
            ->assertSee('MV BINTANG TIMUR');

        // Filter jenis = outward menyembunyikan yang inward.
        $this->actingAs($user)->get(route('manifests.index', ['jenis' => 'outward']))
            ->assertOk()
            ->assertSee('MV BINTANG TIMUR')
            ->assertDontSee('MV SINAR JAYA');

        // Pencarian voyage.
        $this->actingAs($user)->get(route('manifests.index', ['q' => 'V-2401']))
            ->assertOk()
            ->assertSee('MV SINAR JAYA')
            ->assertDontSee('MV BINTANG TIMUR');
    }

    public function test_sync_without_credential_flashes_error(): void
    {
        $this->actingAs($this->user())
            ->post(route('manifests.sync'))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_all_staff_see_company_manifests(): void
    {
        // Manifes milik perusahaan: seluruh staf melihat data yang sama.
        $owner = $this->user();
        $other = $this->user();
        Manifest::create(['user_id' => $other->id, 'jenis_manifes' => 'inward', 'nama_sarana' => 'KAPAL REKAN KERJA']);

        $this->actingAs($owner)->get(route('manifests.index'))
            ->assertOk()
            ->assertSee('KAPAL REKAN KERJA');
    }
}
