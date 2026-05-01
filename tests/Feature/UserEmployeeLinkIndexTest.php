<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserEmployeeLinkIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_hr_sees_linked_employee_column_values(): void
    {
        $tenant = $this->makeTenant('user-link-index-admin');
        $admin = $this->makeUser('admin_hr', $tenant, 'admin-user-link-index@example.test', 'Admin User Link Index');
        $linkedUser = $this->makeUser('employee', $tenant, 'linked-user-index@example.test', 'Linked User Index');
        $unlinkedUser = $this->makeUser('employee', $tenant, 'unlinked-user-index@example.test', 'Unlinked User Index');

        Employee::create([
            'tenant_id' => $tenant->id,
            'user_id' => $linkedUser->id,
            'employee_code' => 'USR-LNK-1',
            'name' => 'Linked Employee For User',
            'email' => 'linked-employee-for-user@example.test',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertOk();
        $response->assertSee('Linked Employee');
        $response->assertSee('USR-LNK-1 - Linked Employee For User');
        $response->assertSee($unlinkedUser->email);
        $response->assertSee('Not linked');
    }

    public function test_manager_sees_tenant_scoped_users_with_valid_linked_employee_column(): void
    {
        $tenantA = $this->makeTenant('user-link-index-tenant-a');
        $tenantB = $this->makeTenant('user-link-index-tenant-b');
        $manager = $this->makeUser('manager', $tenantA, 'manager-user-link-index@example.test', 'Manager User Link Index');
        $employeeUserA = $this->makeUser('employee', $tenantA, 'tenant-a-user-link-index@example.test', 'Tenant A User Link');
        $employeeUserB = $this->makeUser('employee', $tenantB, 'tenant-b-user-link-index@example.test', 'Tenant B User Link');

        Employee::create([
            'tenant_id' => $tenantA->id,
            'user_id' => $employeeUserA->id,
            'employee_code' => 'USR-TA-1',
            'name' => 'Tenant A Linked Employee',
            'email' => 'tenant-a-linked-employee@example.test',
            'status' => 'active',
        ]);

        Employee::create([
            'tenant_id' => $tenantB->id,
            'user_id' => $employeeUserB->id,
            'employee_code' => 'USR-TB-1',
            'name' => 'Tenant B Linked Employee',
            'email' => 'tenant-b-linked-employee@example.test',
            'status' => 'active',
        ]);

        $response = $this->actingAs($manager)->get(route('users.index', ['tenant_id' => $tenantB->id]));

        $response->assertOk();
        $response->assertSee('Linked Employee');
        $response->assertSee('tenant-a-user-link-index@example.test');
        $response->assertSee('USR-TA-1 - Tenant A Linked Employee');
        $response->assertDontSee('tenant-b-user-link-index@example.test');
        $response->assertDontSee('USR-TB-1 - Tenant B Linked Employee');
    }

    protected function makeTenant(string $slug): Tenant
    {
        return Tenant::create([
            'name' => ucwords(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'domain' => $slug.'.test',
            'status' => 'active',
        ]);
    }

    protected function makeUser(string $role, Tenant $tenant, string $email, string $name): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'status' => 'active',
        ]);
    }
}