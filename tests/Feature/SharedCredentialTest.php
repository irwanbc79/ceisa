<?php

namespace Tests\Feature;

use App\Models\CeisaCredential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Kredensial CEISA level PERUSAHAAN: admin mengisi sekali via Pengaturan,
 * seluruh operator memakainya (termasuk token) tanpa tahu password portal.
 */
class SharedCredentialTest extends TestCase
{
    use RefreshDatabase;

    protected function adminWithCredential(): User
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $admin->ceisaCredential()->create([
            'username' => 'mayank.harahap',
            'password' => 'rahasia',
            'api_key' => 'KEY-123',
            'npwp' => '0960208833125000',
        ]);

        return $admin;
    }

    public function test_operator_uses_company_credential_owned_by_admin(): void
    {
        $this->adminWithCredential();

        Http::fake([
            '*user/login*' => Http::response(['access_token' => 'TOK', 'expires_in' => 3600], 200),
            '*/status*' => Http::response([
                'status' => 'Success', 'dataStatus' => [], 'dataRespon' => [],
            ], 200),
        ]);

        $operator = User::factory()->create(['role' => User::ROLE_OPERATOR]);

        // Operator TANPA kredensial sendiri tetap bisa sinkronisasi —
        // sistem memakai kredensial perusahaan milik admin.
        $this->actingAs($operator)
            ->post(route('documents.sync'))
            ->assertRedirect()
            ->assertSessionMissing('error');

        Http::assertSent(fn ($r) => str_contains($r->url(), 'idPerusahaan=960208833125000'));
    }

    public function test_operator_dashboard_recognizes_company_credential(): void
    {
        $this->adminWithCredential();

        $operator = User::factory()->create(['role' => User::ROLE_OPERATOR]);

        // Banner "kredensial belum diisi" TIDAK muncul untuk operator
        // karena kredensial perusahaan sudah terpasang.
        $this->actingAs($operator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Lengkapi kredensial');
    }

    public function test_operator_cannot_update_company_credential(): void
    {
        $this->adminWithCredential();

        $operator = User::factory()->create(['role' => User::ROLE_OPERATOR]);

        $this->actingAs($operator)
            ->post(route('settings.ceisa.update'), [
                'username' => 'jahat',
                'environment' => 'production',
            ])
            ->assertForbidden();

        $this->assertSame('mayank.harahap', CeisaCredential::shared()->username);
    }

    public function test_operator_sees_readonly_settings_and_can_test_connection(): void
    {
        $this->adminWithCredential();

        Http::fake([
            '*user/login*' => Http::response(['access_token' => 'TOK', 'expires_in' => 3600], 200),
        ]);

        $operator = User::factory()->create(['role' => User::ROLE_OPERATOR]);

        $this->actingAs($operator)
            ->get(route('settings.ceisa.edit'))
            ->assertOk()
            ->assertSee('Kredensial dikelola oleh admin.')
            ->assertDontSee('name="password"', false);

        $this->actingAs($operator)
            ->post(route('settings.ceisa.test'))
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_admin_update_modifies_company_row_without_duplicating(): void
    {
        $ownerAdmin = $this->adminWithCredential();
        $otherAdmin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($otherAdmin)
            ->post(route('settings.ceisa.update'), [
                'username' => 'user.baru',
                'npwp' => '0960208833125000',
                'environment' => 'production',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        // Tetap SATU baris kredensial perusahaan — di-update, bukan diduplikasi.
        $this->assertSame(1, CeisaCredential::count());

        $credential = CeisaCredential::shared();
        $this->assertSame('user.baru', $credential->username);
        $this->assertSame($ownerAdmin->id, $credential->user_id);
        // Password lama dipertahankan karena tidak diisi ulang.
        $this->assertSame('rahasia', $credential->password);
    }
}
