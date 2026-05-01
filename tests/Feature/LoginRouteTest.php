<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_login_shows_login_view(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertViewIs('auth.login');
        $response->assertSee('Sign in');
    }

    public function test_post_session_with_valid_credentials_redirects_to_dashboard(): void
    {
        $user = User::factory()->adminHr()->create([
            'email' => 'login-route-admin@example.test',
            'password' => 'password',
        ]);

        $response = $this->post(route('session.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_post_session_with_invalid_credentials_returns_error_message(): void
    {
        $user = User::factory()->adminHr()->create([
            'email' => 'login-route-invalid@example.test',
            'password' => 'password',
        ]);

        $response = $this->from(route('login'))->post(route('session.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'email' => 'Email atau password tidak valid.',
        ]);
        $this->assertGuest();
    }
}