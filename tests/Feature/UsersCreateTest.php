<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersCreateTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────
    //  Form display
    // ─────────────────────────────────────────

    public function test_admin_can_access_create_user_form(): void
    {
        $tenant = $this->makeTenant('create-admin');
        $admin  = $this->makeUser('admin_hr', $tenant, 'admin-create@example.test', 'Admin Create');

        $response = $this->actingAs($admin)->get(route('users.create'));

        $response->assertOk();
        $response->assertSee('Create New User');
        $response->assertSee('Account Information');
        $response->assertSee('Role & Tenant', false);
        $response->assertSee('name="name"',               false);
        $response->assertSee('name="email"',              false);
        $response->assertSee('name="password"',           false);
        $response->assertSee('name="password_confirmation"', false);
        $response->assertSee('name="avatar"',             false);
        $response->assertSee('name="tenant_id"',          false);
        $response->assertSee('name="role_id"',            false);
        $response->assertSee('name="status"',             false);
    }

    public function test_manager_cannot_access_create_user_form(): void
    {
        $tenant  = $this->makeTenant('create-manager');
        $manager = $this->makeUser('manager', $tenant, 'manager-create@example.test', 'Manager Create');

        $this->actingAs($manager)->get(route('users.create'))->assertForbidden();
    }

    public function test_employee_cannot_access_create_user_form(): void
    {
        $tenant   = $this->makeTenant('create-employee');
        $employee = $this->makeUser('employee', $tenant, 'employee-create@example.test', 'Employee Create');

        $this->actingAs($employee)->get(route('users.create'))->assertForbidden();
    }

    // ─────────────────────────────────────────
    //  Successful creation
    // ─────────────────────────────────────────

    public function test_admin_can_create_user_with_valid_data(): void
    {
        $tenant = $this->makeTenant('create-valid');
        $admin  = $this->makeUser('admin_hr', $tenant, 'admin-valid@example.test', 'Admin Valid');

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'tenant_id'             => $tenant->id,
            'name'                  => 'New Employee User',
            'email'                 => 'new-employee@example.test',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'employee',
            'status'                => 'active',
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success', 'User berhasil dibuat.');

        $this->assertDatabaseHas('users', [
            'email'  => 'new-employee@example.test',
            'name'   => 'New Employee User',
            'role'   => 'employee',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_create_user_with_manager_role(): void
    {
        $tenant = $this->makeTenant('create-manager-role');
        $admin  = $this->makeUser('admin_hr', $tenant, 'admin-mgr-role@example.test', 'Admin Mgr');

        $this->actingAs($admin)->post(route('users.store'), [
            'tenant_id'             => $tenant->id,
            'name'                  => 'New Manager User',
            'email'                 => 'new-manager@example.test',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'manager',
            'status'                => 'inactive',
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email'  => 'new-manager@example.test',
            'role'   => 'manager',
            'status' => 'inactive',
        ]);
    }

    // ─────────────────────────────────────────
    //  Validation
    // ─────────────────────────────────────────

    public function test_store_rejects_invalid_email_format(): void
    {
        $tenant = $this->makeTenant('create-email-val');
        $admin  = $this->makeUser('admin_hr', $tenant, 'admin-email-val@example.test', 'Admin Email Val');

        $this->actingAs($admin)->post(route('users.store'), [
            'tenant_id'             => $tenant->id,
            'name'                  => 'Test User',
            'email'                 => 'not-an-email',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'employee',
            'status'                => 'active',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'not-an-email']);
    }

    public function test_store_rejects_duplicate_email(): void
    {
        $tenant  = $this->makeTenant('create-dup-email');
        $admin   = $this->makeUser('admin_hr', $tenant, 'admin-dup@example.test', 'Admin Dup');
        $existing = $this->makeUser('employee', $tenant, 'existing@example.test', 'Existing');

        $this->actingAs($admin)->post(route('users.store'), [
            'tenant_id'             => $tenant->id,
            'name'                  => 'Duplicate',
            'email'                 => 'existing@example.test',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'employee',
            'status'                => 'active',
        ])->assertSessionHasErrors('email');
    }

    public function test_store_rejects_mismatched_password_confirmation(): void
    {
        $tenant = $this->makeTenant('create-pwd-mismatch');
        $admin  = $this->makeUser('admin_hr', $tenant, 'admin-pwd@example.test', 'Admin Pwd');

        $this->actingAs($admin)->post(route('users.store'), [
            'tenant_id'             => $tenant->id,
            'name'                  => 'Test User',
            'email'                 => 'mismatch@example.test',
            'password'              => 'password123',
            'password_confirmation' => 'different456',
            'role'                  => 'employee',
            'status'                => 'active',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'mismatch@example.test']);
    }

    public function test_store_rejects_password_shorter_than_8_chars(): void
    {
        $tenant = $this->makeTenant('create-short-pwd');
        $admin  = $this->makeUser('admin_hr', $tenant, 'admin-short@example.test', 'Admin Short');

        $this->actingAs($admin)->post(route('users.store'), [
            'tenant_id'             => $tenant->id,
            'name'                  => 'Test User',
            'email'                 => 'shortpwd@example.test',
            'password'              => 'abc',
            'password_confirmation' => 'abc',
            'role'                  => 'employee',
            'status'                => 'active',
        ])->assertSessionHasErrors('password');
    }

    public function test_store_rejects_invalid_role(): void
    {
        $tenant = $this->makeTenant('create-bad-role');
        $admin  = $this->makeUser('admin_hr', $tenant, 'admin-badrole@example.test', 'Admin Bad Role');

        $this->actingAs($admin)->post(route('users.store'), [
            'tenant_id'             => $tenant->id,
            'name'                  => 'Test User',
            'email'                 => 'badrole@example.test',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'superadmin',
            'status'                => 'active',
        ])->assertSessionHasErrors('role');
    }

    public function test_store_rejects_missing_tenant(): void
    {
        $tenant = $this->makeTenant('create-no-tenant');
        $admin  = $this->makeUser('admin_hr', $tenant, 'admin-notenant@example.test', 'Admin No Tenant');

        $this->actingAs($admin)->post(route('users.store'), [
            'name'                  => 'Test User',
            'email'                 => 'notenant@example.test',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'employee',
            'status'                => 'active',
        ])->assertSessionHasErrors('tenant_id');
    }

    public function test_manager_cannot_store_user(): void
    {
        $tenant  = $this->makeTenant('create-mgr-store');
        $manager = $this->makeUser('manager', $tenant, 'manager-store@example.test', 'Manager Store');

        $this->actingAs($manager)->post(route('users.store'), [
            'tenant_id'             => $tenant->id,
            'name'                  => 'Hacked User',
            'email'                 => 'hacked@example.test',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'employee',
            'status'                => 'active',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'hacked@example.test']);
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
