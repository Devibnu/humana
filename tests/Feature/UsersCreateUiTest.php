<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersCreateUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_page_is_full_wide_with_outer_card(): void
    {
        $tenant = $this->makeTenant('ui-full-wide');
        $admin  = $this->makeUser('admin_hr', $tenant, 'admin-ui-wide@example.test', 'Admin UI Wide');

        $response = $this->actingAs($admin)->get(route('users.create'));

        $response->assertOk();
        // Full-width outer wrapper
        $response->assertSee('col-12', false);
        $response->assertSee('card mx-4 mb-4', false);
        // Header inside card-header
        $response->assertSee('Create New User');
        $response->assertSee('Back to Users');
    }

    public function test_create_page_has_account_information_column(): void
    {
        $tenant = $this->makeTenant('ui-account-col');
        $admin  = $this->makeUser('admin_hr', $tenant, 'admin-ui-acct@example.test', 'Admin UI Acct');

        $response = $this->actingAs($admin)->get(route('users.create'));

        $response->assertOk();
        $response->assertSee('Account Information');
        $response->assertSee('name="name"',                   false);
        $response->assertSee('name="email"',                  false);
        $response->assertSee('name="password"',               false);
        $response->assertSee('name="password_confirmation"',  false);
        $response->assertSee('name="avatar"',                 false);
    }

    public function test_create_page_has_role_and_tenant_column(): void
    {
        $tenant = $this->makeTenant('ui-role-col');
        $admin  = $this->makeUser('admin_hr', $tenant, 'admin-ui-role@example.test', 'Admin UI Role');

        $response = $this->actingAs($admin)->get(route('users.create'));

        $response->assertOk();
        $response->assertSee('Pilih Peran', false);
        $response->assertSee('name="tenant_id"', false);
        $response->assertSee('name="role_id"',   false);
        $response->assertSee('name="status"',    false);
    }

    public function test_create_page_has_save_and_cancel_buttons(): void
    {
        $tenant = $this->makeTenant('ui-buttons');
        $admin  = $this->makeUser('admin_hr', $tenant, 'admin-ui-btn@example.test', 'Admin UI Btn');

        $response = $this->actingAs($admin)->get(route('users.create'));

        $response->assertOk();
        $response->assertSee('Save User');
        $response->assertSee('Cancel');
        // Buttons are right-aligned
        $response->assertSee('justify-content-end', false);
    }

    public function test_create_page_two_column_layout(): void
    {
        $tenant = $this->makeTenant('ui-two-col');
        $admin  = $this->makeUser('admin_hr', $tenant, 'admin-ui-2col@example.test', 'Admin UI 2col');

        $response = $this->actingAs($admin)->get(route('users.create'));

        $response->assertOk();
        $response->assertSee('col-lg-7', false);
        $response->assertSee('col-lg-5', false);
    }

    // ─────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────

    protected function makeTenant(string $slug): Tenant
    {
        return Tenant::create([
            'name'   => ucfirst(str_replace('-', ' ', $slug)).' Tenant',
            'slug'   => $slug,
            'domain' => $slug.'.test',
            'status' => 'active',
        ]);
    }

    protected function makeUser(string $role, Tenant $tenant, string $email, string $name): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'name'      => $name,
            'email'     => $email,
            'password'  => 'password123',
            'role'      => $role,
            'status'    => 'active',
        ]);
    }
}
