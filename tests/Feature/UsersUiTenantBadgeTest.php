<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersUiTenantBadgeTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Users UI Tenant',
            'slug' => 'users-ui-tenant',
            'domain' => 'users-ui-tenant.test',
            'status' => 'active',
        ]);
    }

    public function test_manager_cannot_access_create_and_edit_user_forms(): void
    {
        $manager = $this->makeUser('manager', 'manager-users-badge@example.test', 'Manager Users Badge');
        $managedUser = $this->makeUser('employee', 'employee-users-badge@example.test', 'Employee Users Badge');

        $this->actingAs($manager)->get(route('users.create'))->assertForbidden();
        $this->actingAs($manager)->get(route('users.edit', $managedUser))->assertForbidden();
    }

    public function test_admin_hr_create_and_edit_forms_show_tenant_dropdown(): void
    {
        $admin = $this->makeUser('admin_hr', 'admin-users-badge@example.test', 'Admin Users Badge');
        $editableUser = $this->makeUser('employee', 'editable-users-badge@example.test', 'Editable Users Badge');

        $createResponse = $this->actingAs($admin)->get(route('users.create'));

        $createResponse->assertOk();
        $createResponse->assertSee('data-testid="tenant-select"', false);
        $createResponse->assertDontSee('Tenant Locked');

        $editResponse = $this->actingAs($admin)->get(route('users.edit', $editableUser));

        $editResponse->assertOk();
        $editResponse->assertSee('data-testid="tenant-select"', false);
        $editResponse->assertDontSee('Tenant Locked');
    }

    protected function makeUser(string $role, string $email, string $name): User
    {
        return User::create([
            'tenant_id' => $this->tenant->id,
            'name' => $name,
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'status' => 'active',
        ]);
    }
}