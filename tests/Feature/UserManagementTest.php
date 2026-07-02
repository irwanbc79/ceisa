<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    protected function operator(): User
    {
        return User::factory()->create(['role' => User::ROLE_OPERATOR]);
    }

    public function test_guest_is_redirected_from_user_management(): void
    {
        $this->get(route('users.index'))->assertRedirect(route('login'));
    }

    public function test_operator_cannot_access_user_management(): void
    {
        $this->actingAs($this->operator())
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_admin_sees_user_list(): void
    {
        $admin = $this->admin();
        $operator = $this->operator();

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Manajemen Pengguna')
            ->assertSee($admin->email)
            ->assertSee($operator->email);
    }

    public function test_admin_creates_operator_with_generated_password(): void
    {
        $this->actingAs($this->admin())
            ->post(route('users.store'), [
                'name' => 'Staf Baru',
                'email' => 'staf@m2b.co.id',
                'role' => User::ROLE_OPERATOR,
                'password' => null,
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('generated_credentials');

        $user = User::where('email', 'staf@m2b.co.id')->first();

        $this->assertNotNull($user);
        $this->assertSame(User::ROLE_OPERATOR, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_created_user_can_login_with_chosen_password(): void
    {
        $this->actingAs($this->admin())->post(route('users.store'), [
            'name' => 'Staf Login',
            'email' => 'login@m2b.co.id',
            'role' => User::ROLE_OPERATOR,
            'password' => 'RahasiaKuat123',
        ]);

        auth()->logout();

        $this->post('/login', [
            'email' => 'login@m2b.co.id',
            'password' => 'RahasiaKuat123',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_duplicate_email_is_rejected(): void
    {
        $existing = $this->operator();

        $this->actingAs($this->admin())
            ->post(route('users.store'), [
                'name' => 'Dobel',
                'email' => $existing->email,
                'role' => User::ROLE_OPERATOR,
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_admin_resets_password_and_gets_new_credentials_once(): void
    {
        $operator = $this->operator();
        $oldHash = $operator->password;

        $this->actingAs($this->admin())
            ->post(route('users.reset-password', $operator))
            ->assertRedirect()
            ->assertSessionHas('generated_credentials');

        $this->assertNotSame($oldHash, $operator->fresh()->password);
    }

    public function test_admin_changes_role_but_not_own_role(): void
    {
        $admin = $this->admin();
        $operator = $this->operator();

        $this->actingAs($admin)
            ->put(route('users.update', $operator), ['role' => User::ROLE_ADMIN])
            ->assertRedirect();

        $this->assertSame(User::ROLE_ADMIN, $operator->fresh()->role);

        $this->actingAs($admin)
            ->put(route('users.update', $admin), ['role' => User::ROLE_OPERATOR])
            ->assertSessionHas('error');

        $this->assertSame(User::ROLE_ADMIN, $admin->fresh()->role);
    }

    public function test_admin_deactivates_user_but_not_self(): void
    {
        $admin = $this->admin();
        $operator = $this->operator();

        $this->actingAs($admin)
            ->post(route('users.toggle-active', $operator))
            ->assertRedirect();

        $this->assertFalse($operator->fresh()->is_active);

        $this->actingAs($admin)
            ->post(route('users.toggle-active', $admin))
            ->assertSessionHas('error');

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_deactivated_user_cannot_login(): void
    {
        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'is_active' => false,
        ]);

        $this->post('/login', [
            'email' => $operator->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_deactivated_user_is_logged_out_mid_session(): void
    {
        $operator = $this->operator();

        $this->actingAs($operator)->get(route('dashboard'))->assertOk();

        $operator->update(['is_active' => false]);

        $this->actingAs($operator)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
