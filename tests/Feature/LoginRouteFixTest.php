<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginRouteFixTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_login_shows_login_view(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertViewIs('auth.login');
        $response->assertSee('Sign in');
    }

    public function test_get_login_uses_tenant_branding_in_guest_navbar(): void
    {
        Tenant::create([
            'name' => 'Jasa ibnu',
            'code' => 'JASAIBNU',
            'slug' => 'jasa-ibnu',
            'domain' => 'jasa-ibnu.test',
            'status' => 'active',
        ]);

        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('data-testid="guest-tenant-brand"', false);
        $response->assertSee('Jasa ibnu');
        $response->assertDontSee('Soft UI Dashboard Laravel');
    }

    public function test_get_login_without_custom_footer_uses_tenant_default_footer_text(): void
    {
        Tenant::create([
            'name' => 'Jasa ibnu',
            'code' => 'JASAIBNU',
            'slug' => 'jasa-ibnu-default-footer',
            'domain' => 'jasa-ibnu-default-footer.test',
            'status' => 'active',
        ]);

        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Copyright (c) '.now()->year.' Jasa ibnu. All rights reserved.');
        $response->assertDontSee('Soft by');
    }

    public function test_get_login_uses_tenant_custom_footer_text(): void
    {
        Tenant::create([
            'name' => 'Jasa ibnu',
            'code' => 'JASAIBNU',
            'slug' => 'jasa-ibnu',
            'domain' => 'jasa-ibnu-footer.test',
            'status' => 'active',
            'login_footer_text' => 'Copyright (c) {year} Jasa ibnu. All rights reserved.',
        ]);

        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Copyright (c) '.now()->year.' Jasa ibnu. All rights reserved.');
        $response->assertDontSee('Soft by');
    }

    public function test_post_session_valid_redirects_to_dashboard(): void
    {
        $user = User::factory()->adminHr()->create([
            'email' => 'login-route-fix-admin@example.test',
            'password' => 'password',
        ]);

        $response = $this->post(route('session.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_post_session_invalid_returns_error(): void
    {
        $user = User::factory()->adminHr()->create([
            'email' => 'login-route-fix-invalid@example.test',
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

    public function test_get_session_redirects_to_login(): void
    {
        $response = $this->get('/session');

        $response->assertRedirect(route('login'));
    }
}