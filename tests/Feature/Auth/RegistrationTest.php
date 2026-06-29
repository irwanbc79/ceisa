<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Registrasi publik sengaja dinonaktifkan (lihat commit d370f32 —
     * login sebagai homepage, akun dibuat admin via `php artisan admin:create`).
     * Test ini menjaga keputusan itu agar route /register tidak hidup lagi.
     */
    public function test_registration_screen_is_disabled(): void
    {
        $response = $this->get('/register');

        $response->assertNotFound();
    }

    public function test_registration_endpoint_is_disabled(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertNotFound();
        $this->assertGuest();
    }
}
